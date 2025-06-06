<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Basket\Product;
use App\Services\Basket\Basket;
use App\Services\Basket\DeliveryRules;
use App\Services\Basket\Offers\BuyOneGetOneHalfPrice;

class BasketWebController extends Controller
{

    /**
     * Get the products from the config
     * @return array
     */
    protected function getProducts(): array
    {
        // Initialize the products array
        $products = []; 

        // Loop through the products from the config and create a new Product object for each one
        foreach (config('products') as $p) {
            if (!isset($p['code']) || !isset($p['name']) || !isset($p['price'])) {
                throw new \InvalidArgumentException('Product configuration is missing required fields');
            }
            $products[$p['code']] = new Product($p['code'], $p['name'], $p['price']);
        }

        // If no products are found, throw an exception
        if (empty($products)) {
            throw new \InvalidArgumentException('No products found in configuration');
        }

        return $products;
    }

    /**
     * Show the form to add products to the basket
     * @return \Illuminate\Contracts\View\View
     */
    public function showForm()
    {
        $offers = [];
        $offerTexts = [];
        $products = $this->getProducts();

        // Get offers from config
        foreach (config('offers.buy_one_get_half_price.products') as $productCode) {
            $offer = new BuyOneGetOneHalfPrice($productCode);
            $offers[] = $productCode;
            $offerTexts[$productCode] = $offer->getDisplayText();
        }

        return view('basket', [
            'products' => $products,
            'offers' => $offers,
            'offerTexts' => $offerTexts
        ]);
    }

    /**
     * Calculate the total price of the basket
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function calculate(Request $request)
    {
        $offers = [];
        $offerTexts = [];
        $products = $this->getProducts();

        // Get delivery rules from config
        $deliveryRules = new DeliveryRules(config('delivery.rules'));

        // Get offers from config
        foreach (config('offers.buy_one_get_half_price.products') as $productCode) {
            $offer = new BuyOneGetOneHalfPrice($productCode);
            $offers[] = $offer;
            $offerTexts[$productCode] = $offer->getDisplayText();
        }

        // Create the basket
        $basket = new Basket($products, $deliveryRules, $offers);

        // Get the items from the request
        $items = $request->input('items', []);

        // Add the items to the basket
        foreach ($items as $code => $quantity) {
            // If the item is not in the products or the quantity is less than 1, skip
            if (!isset($products[$code]) || (int)$quantity < 1) {
                continue;
            }

            // Add the item to the basket
            for ($i = 0; $i < (int)$quantity; $i++) {
                $basket->add($code);
            }
        }

        // Get product codes with offers
        $productsWithOffers = [];
        foreach (config('offers.buy_one_get_half_price.products') as $productCode) {
            $productsWithOffers[] = $productCode;
        }

        // Return the view with the products, total and selected items
        return view('basket', [
            'products' => $products,
            'total' => $basket->total(),
            'subtotal' => $basket->getSubtotal(),
            'discounts' => $basket->getDiscounts(),
            'deliveryCost' => $basket->getDeliveryCost(),
            'productDiscounts' => $basket->getProductDiscounts(),
            'selected' => $items,
            'offers' => $productsWithOffers,
            'offerTexts' => $offerTexts
        ]);
    }

    /**
     * Calculate the total price of the basket and return as JSON
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateAjax(Request $request)
    {
        try {
            $offers = [];
            $products = $this->getProducts();

            // Get delivery rules from config
            $deliveryRules = new DeliveryRules(config('delivery.rules'));

            // Get offers from config
            foreach (config('offers.buy_one_get_half_price.products') as $productCode) {
                $offers[] = new BuyOneGetOneHalfPrice($productCode);
            }

            // Create the basket
            $basket = new Basket($products, $deliveryRules, $offers);

            // Get the items from the request
            $items = $request->input('items', []);

            // Add the items to the basket
            foreach ($items as $code => $quantity) {
                if (!isset($products[$code])) {
                    continue;
                }

                $quantity = (int)$quantity;
                if ($quantity < 1) {
                    continue;
                }

                // Add the quantity to the basket
                for ($i = 0; $i < $quantity; $i++) {
                    $basket->add($code);
                }
            }

            $subtotal = $basket->getSubtotal();
            $deliveryCost = $basket->getDeliveryCost();
            $deliveryRule = $deliveryRules->getAppliedRule($subtotal);

            return response()->json([
                'subtotal' => $subtotal,
                'discounts' => $basket->getDiscounts(),
                'deliveryCost' => $deliveryCost,
                'deliveryRule' => $deliveryRule,
                'total' => $basket->total()
            ]);
        } catch (\Exception $e) {
            \Log::error('Basket calculation error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $basket = new Basket();
        $items = $request->input('items', []);

        // Add the items to the basket
        foreach ($items as $code => $quantity) {
            $basket->add($code, $quantity);
        }

        $products = Product::all();
        $offers = config('offers.offers');
        $offerTexts = [];

        // Get the offer texts
        foreach ($offers as $offer) {
            $offerClass = new $offer['class']($offer['products']);
            foreach ($offer['products'] as $productCode) {
                $offerTexts[$productCode] = $offerClass->getDisplayText();
            }
        }

        // Add the offer information to the products
        $products->each(function ($product) use ($offerTexts) {
            $product->has_offer = isset($offerTexts[$product->code]);
            if ($product->has_offer) {
                $product->offer_text = $offerTexts[$product->code];
            }
        });

        $subtotal = $basket->subtotal();
        $discounts = $basket->discounts();
        $deliveryCost = $basket->deliveryCost();
        $total = $basket->total();

        return view('basket', compact('products', 'items', 'subtotal', 'discounts', 'deliveryCost', 'total'));
    }
}
