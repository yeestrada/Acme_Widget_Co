<?php

namespace App\Services\Basket\Offers;

use App\Services\Basket\Product;

/**
 * Buy one get one half price offer
 * If the product code is R01, the price of the second product is half price
 */
class BuyOneGetOneHalfPrice implements OfferInterface
{
    protected string $productCode;

    /**
     * Create a new instance of the offer
     * @param string $productCode
     */
    public function __construct(string $productCode)
    {
        $this->productCode = $productCode;
    }

    /**
     * Check if the offer applies to the product
     * @param string $productCode
     * @return bool
     */
    public function appliesTo(string $productCode): bool
    {
        return $productCode === $this->productCode;
    }

    /**
     * Apply the offer to the product
     * @param Product $product
     * @param int $quantity
     * @return float
     */
    public function apply(Product $product, int $quantity): float
    {
        // Get the full price of the product
        $fullPrice = $product->price;
        // round down to 2 decimal places
        $halfPrice = floor($fullPrice / 2 * 100) / 100;

        // Get the number of pairs of products
        $pairs = intdiv($quantity, 2);
        // Get the remainder of products
        $remainder = $quantity % 2;

        // Calculate the subtotal
        $subtotal = ($pairs * ($fullPrice + $halfPrice)) + ($remainder * $fullPrice);
        // round down to 2 decimal places
        return floor($subtotal * 100) / 100;
    }

    /**
     * Get the display text for the offer
     * @return string
     */
    public function getDisplayText(): string
    {
        return config('offers.buy_one_get_half_price.display_text', 'Special offer');
    }
}
