<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Basket - Acme Widget Co</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col items-center p-8">

    <div class="w-full max-w-xl bg-white rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">🛒 Acme Widget Co – Shopping Basket</h1>

        <!-- Loader -->
        <div id="loader" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white p-5 rounded-lg shadow-lg">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600 mx-auto"></div>
                <p class="mt-3 text-gray-700">Processing your order...</p>
            </div>
        </div>

        <!-- Success Message -->
        <div id="successMessage" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white p-5 rounded-lg shadow-lg text-center">
                <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Order Completed!</h3>
                <p class="text-gray-600 mb-4">Your purchase has been successfully processed.</p>
                <button onclick="resetBasket()" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Continue Shopping
                </button>
            </div>
        </div>

        <form method="POST" action="/basket" class="space-y-4" id="basketForm">
            @csrf

            <h3 class="text-lg font-semibold">Select your products and quantities:</h3>

            <div class="space-y-4">
                @foreach ($products as $code => $product)
                    <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded p-4 hover:bg-gray-100">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <strong>{{ $product->name }}</strong>
                                <span class="text-sm text-gray-500">({{ $code }})</span>
                                @if(in_array($code, $offers))
                                    <span class="text-sm bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                        {{ $offerTexts[$code] }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-gray-800">${{ number_format($product->price, 2) }}</span>
                                @if(in_array($code, $offers))
                                    <span class="text-sm text-green-600 offer-price-{{ $code }}" style="display: none;">
                                        (Savings: $<span class="discount-amount-{{ $code }}">0.00</span>)
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <input
                                type="number"
                                name="items[{{ $code }}]"
                                min="0"
                                value="{{ isset($selected[$code]) ? $selected[$code] : 0 }}"
                                class="w-20 border border-gray-300 rounded p-1 text-center quantity-input"
                                data-code="{{ $code }}"
                                data-price="{{ $product->price }}"
                                data-has-offer="{{ in_array($code, $offers) ? 'true' : 'false' }}"
                            >
                        </div>
                    </div>
                @endforeach
            </div>
        </form>

        <div class="mt-8 space-y-2 border-t pt-4" id="costBreakdown">
            <div class="flex justify-between text-gray-600">
                <span>Subtotal:</span>
                <span id="subtotal">$0.00</span>
            </div>
            @if($discounts > 0)
                <div class="flex justify-between text-green-600" id="discountsContainer">
                    <span>Offer Savings:</span>
                    <span id="discounts">-${{ number_format($discounts, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between text-gray-600">
                <div>
                    <span>Delivery:</span>
                    <div id="deliveryRule" class="text-xs text-green-600"></div>
                </div>
                <span id="deliveryCost">$0.00</span>
            </div>
            <div class="flex justify-between text-xl font-semibold text-green-700 border-t pt-2 mt-2">
                <span>Total:</span>
                <span id="total">$0.00</span>
            </div>
        </div>

        <div class="mt-8">
            <button type="submit" form="basketForm" id="checkoutButton" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl transition disabled:bg-gray-400 disabled:cursor-not-allowed disabled:hover:bg-gray-400" disabled>
                Checkout
            </button>
        </div>
    </div>

    <script src="{{ asset('js/basket.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeBasket();
        });
    </script>
</body>
</html>
