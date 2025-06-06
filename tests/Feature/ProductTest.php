<?php

namespace Tests\Unit;

use App\Services\Basket\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function test_product_creation(): void
    {
        $product = new Product('R01', 'Red Widget', 32.95);

        $this->assertEquals('R01', $product->code);
        $this->assertEquals('Red Widget', $product->name);
        $this->assertEquals(32.95, $product->price);
    }
} 