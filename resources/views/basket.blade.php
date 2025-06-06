<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Acme Widget Basket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col items-center p-8">

    <div class="w-full max-w-xl bg-white rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">🛒 Acme Widget Co – Shopping Basket</h1>

        <form method="POST" action="/basket" class="space-y-4">
            @csrf

            <h3 class="text-lg font-semibold">Select your products and quantities:</h3>

            <div class="space-y-4">
                @foreach ($products as $code => $product)
                    <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded p-4 hover:bg-gray-100">
                        <div>
                            <strong>{{ $product->name }}</strong> ({{ $code }}) – ${{ number_format($product->price, 2) }}
                        </div>
                        <div>
                            <input
                                type="number"
                                name="items[{{ $code }}]"
                                min="0"
                                value="{{ isset($selected[$code]) ? $selected[$code] : 0 }}"
                                class="w-20 border border-gray-300 rounded p-1 text-center"
                            >
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-6">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-xl transition">
                    Calculate total
                </button>
            </div>
        </form>

        @isset($total)
            <div class="mt-6 text-center text-xl font-semibold text-green-700">
                Total with delivery and discounts: <span class="text-green-900">${{ number_format($total, 2) }}</span>
            </div>
        @endisset
    </div>

</body>
</html>
