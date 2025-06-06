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
            $products[$p['code']] = new Product($p['code'], $p['name'], $p['price']);
        }

        return $products;
    }

    /**
     * Show the form to add products to the basket
     * @return \Illuminate\Contracts\View\View
     */
    public function showForm()
    {
        return view('basket', ['products' => $this->getProducts()]);
    }

    /**
     * Calculate the total price of the basket
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function calculate(Request $request)
    {
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
            if (!isset($products[$code]) || (int)$quantity < 1) {
                continue;
            }

            for ($i = 0; $i < (int)$quantity; $i++) {
                $basket->add($code);
            }
        }

        // Return the view with the products, total and selected items
        return view('basket', [
            'products' => $products,
            'total' => $basket->total(),
            'selected' => $items,
        ]);
    }
}
