<?php

namespace App\Services\Basket;

use App\Services\Basket\DeliveryRules;

/**
 * Basket class
 * This class is used to add products to the basket and calculate the total price
 */
class Basket
{
    protected array $products = [];
    protected array $items = [];
    protected DeliveryRules $deliveryRules;
    protected array $offers = [];

    /**
     * Create a new instance of the basket
     * @param array $products
     * @param DeliveryRules $deliveryRules
     * @param array $offers
     */
    public function __construct(array $products, DeliveryRules $deliveryRules, array $offers = [])
    {
        $this->products = $products;
        $this->deliveryRules = $deliveryRules;
        $this->offers = $offers;
    }

    /**
     * Add a product to the basket
     * @param string $productCode
     */
    public function add(string $productCode): void
    {
        // Check if the product code is valid
        if (!isset($this->products[$productCode])) {
            throw new \InvalidArgumentException("Producto no válido: $productCode");
        }

        // Check if the product is already in the basket
        if (!isset($this->items[$productCode])) {
            $this->items[$productCode] = [
                'product' => $this->products[$productCode],
                'quantity' => 0
            ];
        }

        // Increment the quantity of the product
        $this->items[$productCode]['quantity']++;
    }

    /**
     * Calculate the total price of the basket
     * @return float
     */
    public function total(): float
    {
        $subtotal = 0;

        // Calculate the subtotal of the basket
        foreach ($this->items as $code => $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];

            // Apply the offers to the product
            foreach ($this->offers as $offer) {
                if ($offer->appliesTo($product->code)) {
                    $subtotal += $offer->apply($product, $quantity);
                    continue 2;
                }
            }

            // Add the price of the product to the subtotal
            $subtotal += $product->price * $quantity;
        }

        // Calculate the delivery cost
        $deliveryCost = $this->deliveryRules->getCost($subtotal);

        // Return the total price of the basket
        return round($subtotal + $deliveryCost, 2);
    }
}
