<?php

namespace App\Services\Basket\Offers;

use App\Services\Basket\Product;

/**
 * Interface for offers
 * Offers are applied to products in the basket
 */
interface OfferInterface
{
    public function appliesTo(string $productCode): bool;
    public function apply(Product $product, int $quantity): float;
    public function getDisplayText(): string;
}