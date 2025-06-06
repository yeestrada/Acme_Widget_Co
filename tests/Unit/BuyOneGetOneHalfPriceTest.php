<?php

namespace Tests\Unit;

use App\Services\Basket\Product;
use App\Services\Basket\Offers\BuyOneGetOneHalfPrice;
use Tests\TestCase;

class BuyOneGetOneHalfPriceTest extends TestCase
{
    private Product $product;
    private BuyOneGetOneHalfPrice $offer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->product = new Product('R01', 'Red Widget', 32.95);
        $this->offer = new BuyOneGetOneHalfPrice('R01');
    }

    public function test_offer_applies_to_correct_product(): void
    {
        $this->assertTrue($this->offer->appliesTo('R01'));
        $this->assertFalse($this->offer->appliesTo('G01'));
    }

    public function test_offer_with_single_product(): void
    {
        $total = $this->offer->apply($this->product, 1);
        $this->assertEquals(32.95, $total);
    }

    public function test_offer_with_pair_of_products(): void
    {
        $total = $this->offer->apply($this->product, 2);
        $expected = 32.95 + floor(32.95 / 2 * 100) / 100;
        $this->assertEquals($expected, $total);
    }

    public function test_offer_with_three_products(): void
    {
        $total = $this->offer->apply($this->product, 3);
        $expected = (32.95 + floor(32.95 / 2 * 100) / 100) + 32.95;
        $this->assertEquals($expected, $total);
    }
} 