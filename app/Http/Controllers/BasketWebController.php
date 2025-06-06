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
            'offerTexts' => $offerTexts,
            'discounts' => 0,
            'subtotal' => 0,
            'total' => 0,
            'deliveryCost' => 0,
            'selected' => []
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
     * Calculate basket costs via AJAX
     */
    public function calculateAjax(Request $request)
    {
        try {
            $products = $this->getProducts();
            $deliveryRules = new DeliveryRules(config('delivery.rules'));
            
            // Get offers from config
            $offers = [];
            foreach (config('offers.buy_one_get_half_price.products') as $productCode) {
                $offers[] = new BuyOneGetOneHalfPrice($productCode);
            }
            
            // Create basket with required dependencies
            $basket = new Basket($products, $deliveryRules, $offers);
            
            // Add products to basket
            $requestProducts = $request->input('products', []);
            foreach ($requestProducts as $product) {
                if (!empty($product['quantity']) && $product['quantity'] > 0) {
                    // Add the product the specified number of times
                    for ($i = 0; $i < $product['quantity']; $i++) {
                        $basket->add($product['code']);
                    }
                }
            }
            
            // Get costs
            $subtotal = $basket->getSubtotal();
            $discounts = $basket->getDiscounts();
            $deliveryCost = $basket->getDeliveryCost();
            $total = $basket->total();
            
            // Get applied delivery rule
            $deliveryRule = $basket->getAppliedRule();
            
            // Get applied offers
            $appliedOffers = [];
            foreach ($basket->getItems() as $code => $item) {
                if (isset($products[$code]) && $item['quantity'] > 0) {
                    $product = $products[$code];
                    $hasOffer = false;
                    $discount = 0;
                    
                    foreach ($offers as $offer) {
                        if ($offer->appliesTo($code)) {
                            $hasOffer = true;
                            $discount = $offer->calculateDiscount($item['quantity'], $product->price);
                            break;
                        }
                    }
                    
                    if ($hasOffer) {
                        $appliedOffers[$code] = [
                            'hasOffer' => true,
                            'discount' => $discount
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => $subtotal,
                    'discounts' => $discounts,
                    'deliveryCost' => $deliveryCost,
                    'total' => $total,
                    'deliveryRule' => $deliveryRule,
                    'appliedOffers' => $appliedOffers
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
