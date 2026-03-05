<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record Stock Movement') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('inventory.store') }}" class="space-y-6">
                        @csrf

                        <!-- Product Selector -->
                        <div>
                            <x-input-label for="product_id" :value="__('Product')" />
                            <select id="product_id" name="product_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select a Product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->sku }} - {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                        </div>

                        <!-- Movement Type -->
                        <div>
                            <x-input-label for="type" :value="__('Movement Type')" />
                            <select id="type" name="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required onchange="toggleStores()">
                                <option value="">Select Type</option>
                                <option value="sale" {{ old('type') == 'sale' ? 'selected' : '' }}>Sale (Deduct from Source)</option>
                                <option value="transfer" {{ old('type') == 'transfer' ? 'selected' : '' }}>Internal Transfer</option>
                                <option value="procurement" {{ old('type') == 'procurement' ? 'selected' : '' }}>Procurement (Add to Destination)</option>
                                <option value="adjustment" {{ old('type') == 'adjustment' ? 'selected' : '' }}>Manual Adjustment</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Source Store -->
                            <div id="source_container">
                                <x-input-label for="source_store_id" :value="__('Source Store')" />
                                <select id="source_store_id" name="source_store_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">N/A (External)</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ old('source_store_id') == $store->id ? 'selected' : '' }}>
                                            [{{ $store->branch->name }}] {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Destination Store -->
                            <div id="destination_container">
                                <x-input-label for="destination_store_id" :value="__('Destination Store')" />
                                <select id="destination_store_id" name="destination_store_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">N/A (External)</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ old('destination_store_id') == $store->id ? 'selected' : '' }}>
                                            [{{ $store->branch->name }}] {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div>
                            <x-input-label for="quantity" :value="__('Quantity')" />
                            <x-text-input id="quantity" class="block mt-1 w-full" type="number" name="quantity" :value="old('quantity')" required min="1" />
                            <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                        </div>

                        <!-- Reference -->
                        <div>
                            <x-input-label for="reference" :value="__('Reference / Reason')" />
                            <x-text-input id="reference" class="block mt-1 w-full" type="text" name="reference" :value="old('reference')" placeholder="Order #, Invoice #, etc." />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end">
                            <x-secondary-button type="button" onclick="window.history.back()" class="mr-4">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button>
                                Record Movement
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleStores() {
            const type = document.getElementById('type').value;
            const source = document.getElementById('source_container');
            const destination = document.getElementById('destination_container');

            // Simple logic to help user
            if (type === 'sale') {
                document.getElementById('source_store_id').required = true;
                document.getElementById('destination_store_id').value = "";
            } else if (type === 'procurement') {
                document.getElementById('source_store_id').value = "";
                document.getElementById('destination_store_id').required = true;
            } else if (type === 'transfer') {
                document.getElementById('source_store_id').required = true;
                document.getElementById('destination_store_id').required = true;
            }
        }
        // Run once on load
        toggleStores();
    </script>
</x-app-layout>
