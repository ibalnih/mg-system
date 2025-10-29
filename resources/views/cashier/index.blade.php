<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Pestapora System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}" />
    <link rel="icon" href="{{ asset('icons/favicon.ico') }}" sizes="any" />
</head>

<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Kasir POS</h1>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">{{ Auth::user()->name }}</span>
                    <button onclick="showPendingOrders()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                        Pesanan Pending <span id="pending-count" class="bg-white text-yellow-600 px-2 py-1 rounded-full text-sm ml-1">0</span>
                    </button>
                    <form method="POST" action="{{ route('cashier.logout') }}">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Daftar Menu</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="products-grid">
                    @foreach($products as $product)
                    <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition {{ $product->stock > 0 ? 'cursor-pointer' : 'opacity-50' }}"
                        @if($product->stock > 0)
                        onclick='addToCart({{ $product->id }}, {{ json_encode($product->name) }}, {{ $product->price }}, {{ $product->stock }})'
                        @endif>
                        <h3 class="font-semibold text-gray-800 mb-2">{{ $product->name }}</h3>
                        <p class="text-sm text-gray-600 mb-2">{{ $product->description }}</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-blue-600">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                            <span class="text-sm {{ $product->stock > 0 ? 'text-green-600' : 'text-red-600' }}">
                                Stok: {{ $product->stock }}
                            </span>
                        </div>
                        @if($product->stock <= 0)
                            <span class="text-xs text-red-500 font-semibold">Tidak Tersedia</span>
                            @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Keranjang</h2>

                    <div class="mb-4">
                        <label for="table-number" class="block text-gray-700 font-semibold mb-2">Nomor Meja *</label>
                        <input
                            type="text"
                            id="table-number"
                            placeholder="Contoh: 5"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div id="cart-items" class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                        <p class="text-gray-500 text-center py-8">Keranjang kosong</p>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-lg font-semibold">Total:</span>
                            <span id="total-amount" class="text-2xl font-bold text-blue-600">Rp 0</span>
                        </div>

                        <button
                            onclick="checkout()"
                            id="checkout-btn"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            Proses Pesanan
                        </button>

                        <button
                            onclick="clearCart()"
                            class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 rounded-lg mt-2">
                            Bersihkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pending-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Pesanan Pending</h2>
                    <button onclick="closePendingModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div id="pending-orders-content" class="p-6 overflow-y-auto max-h-[70vh]">
            </div>
        </div>
    </div>

    <script>
        let cart = [];

        function addToCart(id, name, price, stock) {
            const existingItem = cart.find(item => item.id === id);

            if (existingItem) {
                if (existingItem.quantity < stock) {
                    existingItem.quantity++;
                } else {
                    alert('Stok tidak cukup!');
                    return;
                }
            } else {
                cart.push({
                    id,
                    name,
                    price,
                    quantity: 1,
                    stock
                });
            }

            renderCart();
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            renderCart();
        }

        function updateQuantity(id, change) {
            const item = cart.find(item => item.id === id);
            if (item) {
                const newQty = item.quantity + change;
                if (newQty > 0 && newQty <= item.stock) {
                    item.quantity = newQty;
                } else if (newQty <= 0) {
                    removeFromCart(id);
                    return;
                } else {
                    alert('Stok tidak cukup!');
                    return;
                }
                renderCart();
            }
        }

        function renderCart() {
            const cartContainer = document.getElementById('cart-items');
            const checkoutBtn = document.getElementById('checkout-btn');

            if (cart.length === 0) {
                cartContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Keranjang kosong</p>';
                checkoutBtn.disabled = true;
                document.getElementById('total-amount').textContent = 'Rp 0';
                return;
            }

            checkoutBtn.disabled = false;

            let html = '';
            let total = 0;

            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;

                html += `
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800">${item.name}</h4>
                            <p class="text-sm text-gray-600">Rp ${item.price.toLocaleString('id-ID')}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="updateQuantity(${item.id}, -1)" class="bg-red-500 text-white w-8 h-8 rounded hover:bg-red-600">-</button>
                            <span class="font-semibold w-8 text-center">${item.quantity}</span>
                            <button onclick="updateQuantity(${item.id}, 1)" class="bg-green-500 text-white w-8 h-8 rounded hover:bg-green-600">+</button>
                        </div>
                        <div class="ml-4 text-right">
                            <p class="font-semibold text-gray-800">Rp ${subtotal.toLocaleString('id-ID')}</p>
                            <button onclick="removeFromCart(${item.id})" class="text-red-500 text-xs hover:text-red-700">Hapus</button>
                        </div>
                    </div>
                `;
            });

            cartContainer.innerHTML = html;
            document.getElementById('total-amount').textContent = `Rp ${total.toLocaleString('id-ID')}`;
        }

        function clearCart() {
            if (confirm('Yakin ingin mengosongkan keranjang?')) {
                cart = [];
                document.getElementById('table-number').value = '';
                renderCart();
            }
        }

        async function checkout() {
            const tableNumber = document.getElementById('table-number').value.trim();

            if (!tableNumber) {
                alert('Nomor meja harus diisi!');
                document.getElementById('table-number').focus();
                return;
            }

            if (cart.length === 0) {
                alert('Keranjang kosong!');
                return;
            }

            const items = cart.map(item => ({
                product_id: item.id,
                quantity: item.quantity
            }));

            try {
                const response = await fetch('{{ route("cashier.orders.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        table_number: tableNumber,
                        items
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Pesanan berhasil dibuat!\nNo. Pesanan: ' + data.order.order_number + '\nMeja: ' + tableNumber);
                    cart = [];
                    document.getElementById('table-number').value = '';
                    renderCart();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }

        async function showPendingOrders() {
            try {
                const response = await fetch('{{ route("cashier.orders.pending") }}');
                const orders = await response.json();

                const content = document.getElementById('pending-orders-content');

                if (orders.length === 0) {
                    content.innerHTML = '<p class="text-center text-gray-500 py-8">Tidak ada pesanan pending</p>';
                } else {
                    let html = '<div class="space-y-4">';

                    orders.forEach(order => {
                        html += `
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-bold text-lg">${order.order_number}</h3>
                                        <p class="text-gray-600">Meja: ${order.table_number}</p>
                                        <p class="text-sm text-gray-500">${new Date(order.created_at).toLocaleString('id-ID')}</p>
                                    </div>
                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">Pending</span>
                                </div>
                                
                                <div class="space-y-2 mb-3">
                                    ${order.items.map(item => `
                                        <div class="flex justify-between text-sm">
                                            <span>${item.product.name} x${item.quantity}</span>
                                            <span class="font-semibold">Rp ${parseInt(item.subtotal).toLocaleString('id-ID')}</span>
                                        </div>
                                    `).join('')}
                                </div>
                                
                                <div class="border-t pt-3 flex justify-between items-center">
                                    <span class="font-bold text-lg">Total: Rp ${parseInt(order.total_amount).toLocaleString('id-ID')}</span>
                                    <button onclick="confirmOrder(${order.id})" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                                        Konfirmasi
                                    </button>
                                </div>
                            </div>
                        `;
                    });

                    html += '</div>';
                    content.innerHTML = html;
                }

                document.getElementById('pending-modal').classList.remove('hidden');
            } catch (error) {
                alert('Error loading pending orders: ' + error.message);
            }
        }

        function closePendingModal() {
            document.getElementById('pending-modal').classList.add('hidden');
        }

        async function confirmOrder(orderId) {
            if (!confirm('Konfirmasi pesanan ini?')) return;

            try {
                const response = await fetch(`/cashier/orders/${orderId}/confirm`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('Pesanan berhasil dikonfirmasi!');
                    closePendingModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }

        async function loadPendingCount() {
            try {
                const response = await fetch('{{ route("cashier.orders.pending") }}');
                const orders = await response.json();
                document.getElementById('pending-count').textContent = orders.length;
            } catch (error) {
                console.error('Error loading pending count:', error);
            }
        }

        loadPendingCount();
        setInterval(loadPendingCount, 30000);
    </script>
</body>

</html>