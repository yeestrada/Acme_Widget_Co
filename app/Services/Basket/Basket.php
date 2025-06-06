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
    protected float $subtotal = 0;
    protected float $discounts = 0;
    protected float $deliveryCost = 0;
    protected array $productDiscounts = [];

    /**
     * Create a new instance of the basket
     * @param array $products
     * @param DeliveryRules $deliveryRules
     * @param array $offers
     */
    public function __construct(array $products, DeliveryRules $deliveryRules, array $offers = [])
    {
        if (empty($products)) {
            throw new \InvalidArgumentException('Products array cannot be empty');
        }

        if (!$deliveryRules instanceof DeliveryRules) {
            throw new \InvalidArgumentException('DeliveryRules must be an instance of DeliveryRules');
        }

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
            throw new \InvalidArgumentException("Product not valid: $productCode");
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

        // Reset calculated values
        $this->subtotal = 0;
        $this->discounts = 0;
        $this->deliveryCost = 0;
    }

    /**
     * Get discounts applied to each product
     * @return array
     */
    public function getProductDiscounts(): array
    {
        $this->productDiscounts = [];
        
        foreach ($this->items as $code => $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            $originalPrice = $product->price * $quantity;
            $discountedPrice = $originalPrice;

            foreach ($this->offers as $offer) {
                if ($offer->appliesTo($product->code)) {
                    $discountedPrice = $offer->apply($product, $quantity);
                    break;
                }
            }

            if ($discountedPrice < $originalPrice) {
                $this->productDiscounts[$code] = [
                    'original' => $originalPrice,
                    'discounted' => $discountedPrice,
                    'savings' => $originalPrice - $discountedPrice
                ];
            }
        }

        return $this->productDiscounts;
    }

    /**
     * Calculate the subtotal of the basket
     * @return float
     */
    public function getSubtotal(): float
    {
        if ($this->subtotal > 0) {
            return $this->subtotal;
        }

        $this->subtotal = 0;
        $this->discounts = 0;
        $this->productDiscounts = [];

        foreach ($this->items as $code => $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            $originalPrice = round($product->price * $quantity, 2);
            $this->subtotal += $originalPrice;

            // Apply the offers to the product
            foreach ($this->offers as $offer) {
                if ($offer->appliesTo($product->code)) {
                    $discountedPrice = round($offer->apply($product, $quantity), 2);
                    $this->discounts += round($originalPrice - $discountedPrice, 2);
                    $this->productDiscounts[$code] = [
                        'original' => $originalPrice,
                        'discounted' => $discountedPrice,
                        'savings' => round($originalPrice - $discountedPrice, 2)
                    ];
                    break;
                }
            }
        }

        $this->subtotal = round($this->subtotal, 2);
        return $this->subtotal;
    }

    /**
     * Get the total discounts applied
     * @return float
     */
    public function getDiscounts(): float
    {
        if ($this->discounts > 0) {
            return $this->discounts;
        }

        $this->discounts = 0;
        $this->productDiscounts = [];

        foreach ($this->items as $code => $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            $originalPrice = round($product->price * $quantity, 2);

            foreach ($this->offers as $offer) {
                if ($offer->appliesTo($product->code)) {
                    $discountedPrice = round($offer->apply($product, $quantity), 2);
                    $discount = round($originalPrice - $discountedPrice, 2);
                    $this->discounts += $discount;
                    $this->productDiscounts[$code] = [
                        'original' => $originalPrice,
                        'discounted' => $discountedPrice,
                        'savings' => $discount
                    ];
                    break;
                }
            }
        }

        $this->discounts = round($this->discounts, 2);
        return $this->discounts;
    }

    /**
     * Get the delivery cost
     * @return float
     */
    public function getDeliveryCost(): float
    {
        $subtotal = $this->getSubtotal();
        $discounts = $this->getDiscounts();
        $subtotalAfterDiscounts = round($subtotal - $discounts, 2);
        $this->deliveryCost = $this->deliveryRules->getCost($subtotalAfterDiscounts);
        return round($this->deliveryCost, 2);
    }

    /**
     * Get the items in the basket
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get the applied delivery rule
     * @return array
     */
    public function getAppliedRule(): array
    {
        $subtotal = $this->getSubtotal();
        $discounts = $this->getDiscounts();
        $subtotalAfterDiscounts = round($subtotal - $discounts, 2);
        return $this->deliveryRules->getAppliedRule($subtotalAfterDiscounts);
    }

    /**
     * Calculate the total price of the basket
     * @return float
     */
    public function total(): float
    {
        $subtotal = $this->getSubtotal();
        $discounts = $this->getDiscounts();
        $deliveryCost = $this->getDeliveryCost();
        
        return round($subtotal - $discounts + $deliveryCost, 2);
    }
}
