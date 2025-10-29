<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        $products = Product::with('recipes.rawMaterial')
            ->where('is_available', true)
            ->get()
            ->map(function ($product) {
                $product->stock = $product->available_stock;
                return $product;
            });

        return view('customer.index', compact('products'));
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
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            $order = Order::create([
                'table_number' => $request->table_number,
                'order_type' => 'customer',
                'status' => 'pending',
                'total_amount' => $totalAmount,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat. Menunggu konfirmasi kasir.',
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
}
