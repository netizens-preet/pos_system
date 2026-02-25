<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Place New Order') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="orderForm()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('orders.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                   
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Order Items</h3>
                                <button type="button" @click="addItem()"
                                    class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded text-sm transition">
                                    + Add Product
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                Product</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-24">
                                                Quantity</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-32">
                                                Price</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-32">
                                                Total</th>
                                            <th class="px-4 py-2 text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr>
                                                <td class="px-4 py-2">
                                                    <select x-bind:name="'items[' + index + '][product_id]'"
                                                        x-model="item.product_id" @change="updatePrice(index)"
                                                        class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm text-sm"
                                                        required>
                                                        <option value="">Select Product</option>
                                                        @foreach($products as $product) //Loops through each item in the $items array and repeats the block
                                                            <option value="{{ $product->id }}"
                                                                data-price="{{ $product->price }}"
                                                                data-stock="{{ $product->stock_quantity }}">
                                                                {{ $product->name }} (In Stock:
                                                                {{ $product->stock_quantity }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <input type="number" x-bind:name="'items[' + index + '][quantity]'"
                                                        x-model.number="item.quantity" @input="calculateTotal()"
                                                        class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm"
                                                        min="1" required />
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                    $<span x-text="item.price.toFixed(2)"></span>
                                                </td>
                                                <td
                                                    class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 font-semibold">
                                                    $<span x-text="(item.price * item.quantity).toFixed(2)"></span>
                                                </td>
                                                <td class="px-4 py-2 text-right">
                                                    <button type="button" @click="removeItem(index)"
                                                        class="text-red-500 hover:text-red-700">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Summary</h3>

                            <div class="mb-4">
                                <x-input-label for="customer_id" :value="__('Customer')" />
                                <select id="customer_id" name="customer_id"
                                    class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">$<span
                                            x-text="subtotal.toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Discount:</span>
                                    <input type="number" name="discount" x-model.number="discount"
                                        @input="calculateTotal()"
                                        class="w-24 text-right border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm"
                                        step="0.01" min="0">
                                </div>
                                <div
                                    class="flex justify-between text-lg font-bold pt-3 border-t border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-900 dark:text-gray-100">Total:</span>
                                    <span class="text-indigo-600 dark:text-indigo-400">$<span
                                            x-text="total.toFixed(2)"></span></span>
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-input-label for="note" :value="__('Order Note')" />
                                <textarea name="note" id="note"
                                    class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm"
                                    rows="2"></textarea>
                            </div>

                            <div class="mt-6">
                                <x-primary-button class="w-full justify-center py-3">
                                    Place Order
                                </x-primary-button>
                                <a href="{{ route('orders.index') }}"
                                    class="block text-center mt-3 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function orderForm() {
            return {
                items: [{ product_id: '', quantity: 1, price: 0 }],
                discount: 0,
                subtotal: 0,
                total: 0,

                addItem() {
                    this.items.push({ product_id: '', quantity: 1, price: 0 });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculateTotal();
                },

                updatePrice(index) {
                    const select = event.target;
                    const option = select.options[select.selectedIndex];
                    const price = parseFloat(option.dataset.price || 0);
                    this.items[index].price = price;
                    this.calculateTotal();
                },

                calculateTotal() {
                    this.subtotal = this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    this.total = Math.max(0, this.subtotal - this.discount);
                }
            }
        }
    </script>
</x-app-layout>