<?php

namespace App\Services\Basket;

/**
 * Product class
 * This class is used to create a product
 */
class Product
{
    public string $code;
    public string $name;
    public float $price;

    /**
     * Create a new instance of the product
     * @param string $code
     * @param string $name
     * @param float $price
     */
    public function __construct(string $code, string $name, float $price)
    {
        $this->code = $code;
        $this->name = $name;
        $this->price = $price;
    }
}
