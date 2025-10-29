<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('cashier.index');
        }
        return view('cashier.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('cashier.index');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('cashier.login');
    }

    public function index()
    {
        $products = Product::with('recipes.rawMaterial')
            ->where('is_available', true)
            ->get()
            ->map(function ($product) {
                $product->stock = $product->available_stock;
                return $product;
            });

        return view('cashier.index', compact('products'));
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'table_number' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::with('recipes.rawMaterial')->findOrFail($item['product_id']);

                if (!$product->canBeMade($item['quantity'])) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stok tidak cukup untuk {$product->name}"
                    ], 400);
                }

                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            $order = Order::create([
                'table_number' => $request->table_number,
                'order_type' => 'cashier',
                'cashier_id' => Auth::id(),
                'status' => 'confirmed',
                'total_amount' => $totalAmount,
                'confirmed_at' => now(),
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                $item['product']->reduceStock($item['quantity']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'order' => $order->load('items.product'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pendingOrders()
    {
        $orders = Order::with(['items.product', 'cashier'])
            ->where('order_type', 'customer')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function confirmOrder(Request $request, Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan sudah dikonfirmasi'
            ], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($order->items as $item) {
                $product = Product::with('recipes.rawMaterial')->find($item->product_id);

                if (!$product->canBeMade($item->quantity)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stok tidak cukup untuk {$product->name}"
                    ], 400);
                }
            }

            foreach ($order->items as $item) {
                $product = Product::with('recipes.rawMaterial')->find($item->product_id);
                $product->reduceStock($item->quantity);
            }

            $order->update([
                'status' => 'confirmed',
                'cashier_id' => Auth::id(),
                'confirmed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dikonfirmasi',
                'order' => $order->fresh()->load('items.product', 'cashier'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
