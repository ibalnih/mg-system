<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Menu - Pestapora System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}" />
    <link rel="icon" href="{{ asset('icons/favicon.ico') }}" sizes="any" />
</head>

<body class="bg-gradient-to-br from-purple-50 to-pink-50 min-h-screen">
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <h1 class="text-3xl font-bold text-center bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                Selamat Datang di Pestapora
            </h1>
            <p class="text-center text-gray-600 mt-2">Pesan menu favorit Anda</p>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Daftar Menu</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="products-grid">
                    @foreach($products as $product)
                    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition transform hover:-translate-y-1 {{ $product->stock > 0 ? 'cursor-pointer' : 'opacity-50' }}"
                        @if($product->stock > 0)
                        onclick='addToCart({{ $product->id }}, {{ json_encode($product->name) }}, {{ $product->price }}, {{ $product->stock }})'
                        @endif>
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="font-bold text-xl text-gray-800">{{ $product->name }}</h3>
                            @if($product->stock > 0)
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Tersedia</span>
                            @else
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Habis</span>
                            @endif
                        </div>
                        <p class="text-gray-600 text-sm mb-4">{{ $product->description }}</p>
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-purple-600">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-4">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Pesanan Anda</h2>
                    <div class="mb-6">
                        <label for="table-number" class="block text-gray-700 font-semibold mb-2">Nomor Meja *</label>
                        <input
                            type="text"
                            id="table-number"
                            placeholder="Contoh: 5"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <div id="cart-items" class="space-y-3 mb-6 max-h-96 overflow-y-auto">
                        <p class="text-gray-500 text-center py-8">Belum ada pesanan</p>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center mb-6">
                            <span class="text-xl font-semibold text-gray-700">Total:</span>
                            <span id="total-amount" class="text-3xl font-bold text-purple-600">Rp 0</span>
                        </div>

                        <button
                            onclick="submitOrder()"
                            id="order-btn"
                            class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-4 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition transform hover:scale-105"
                            disabled>
                            Kirim Pesanan
                        </button>

                        <button
                            onclick="clearCart()"
                            class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg mt-3 transition">
                            Bersihkan Pesanan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="success-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl max-w-md w-full p-8 text-center">
            <div class="mb-4">
                <svg class="w-20 h-20 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Pesanan Berhasil!</h2>
            <p class="text-gray-600 mb-6" id="order-number-text">No. Pesanan: -</p>
            <p class="text-sm text-gray-500 mb-6">Pesanan Anda sedang menunggu konfirmasi dari kasir. Mohon tunggu sebentar.</p>
            <button onclick="closeSuccessModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-8 py-3 rounded-lg">
                OK
            </button>
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
            const orderBtn = document.getElementById('order-btn');
            const tableNumber = document.getElementById('table-number').value.trim();

            if (cart.length === 0) {
                cartContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Belum ada pesanan</p>';
                orderBtn.disabled = true;
                document.getElementById('total-amount').textContent = 'Rp 0';
                return;
            }

            orderBtn.disabled = !(cart.length > 0 && tableNumber);

            let html = '';
            let total = 0;

            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;

                html += `
                    <div class="flex justify-between items-center p-4 bg-purple-50 rounded-lg">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800">${item.name}</h4>
                            <p class="text-sm text-gray-600">Rp ${item.price.toLocaleString('id-ID')}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="updateQuantity(${item.id}, -1)" class="bg-red-500 text-white w-8 h-8 rounded-lg hover:bg-red-600 transition">-</button>
                            <span class="font-bold w-10 text-center">${item.quantity}</span>
                            <button onclick="updateQuantity(${item.id}, 1)" class="bg-green-500 text-white w-8 h-8 rounded-lg hover:bg-green-600 transition">+</button>
                        </div>
                        <div class="ml-4 text-right">
                            <p class="font-bold text-gray-800">Rp ${subtotal.toLocaleString('id-ID')}</p>
                            <button onclick="removeFromCart(${item.id})" class="text-red-500 text-xs hover:text-red-700">Hapus</button>
                        </div>
                    </div>
                `;
            });

            cartContainer.innerHTML = html;
            document.getElementById('total-amount').textContent = `Rp ${total.toLocaleString('id-ID')}`;
        }

        function clearCart() {
            if (confirm('Yakin ingin mengosongkan pesanan?')) {
                cart = [];
                renderCart();
            }
        }

        async function submitOrder() {
            const tableNumber = document.getElementById('table-number').value.trim();

            if (!tableNumber) {
                alert('Mohon masukkan nomor meja!');
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
                const response = await fetch('{{ route("customer.orders.store") }}', {
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
                    document.getElementById('order-number-text').textContent = 'No. Pesanan: ' + data.order.order_number;
                    document.getElementById('success-modal').classList.remove('hidden');
                    cart = [];
                    document.getElementById('table-number').value = '';
                    renderCart();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }

        function closeSuccessModal() {
            document.getElementById('success-modal').classList.add('hidden');
        }

        document.getElementById('table-number').addEventListener('input', function() {
            const orderBtn = document.getElementById('order-btn');
            const tableNumber = this.value.trim();
            orderBtn.disabled = !(cart.length > 0 && tableNumber);
        });
    </script>
</body>

</html>