<?php

namespace Tests\Unit;

use App\Services\Basket\Product;
use App\Services\Basket\Basket;
use App\Services\Basket\DeliveryRules;
use App\Services\Basket\Offers\BuyOneGetOneHalfPrice;
use Tests\TestCase;

class BasketTest extends TestCase
{
    private array $products;
    private DeliveryRules $deliveryRules;
    private array $offers;
    private Basket $basket;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->products = [
            'R01' => new Product('R01', 'Red Widget', 32.95),
            'G01' => new Product('G01', 'Green Widget', 24.95),
            'B01' => new Product('B01', 'Blue Widget', 7.95),
        ];

        $this->deliveryRules = new DeliveryRules([
            ['limit' => 50.0, 'cost' => 4.95],
            ['limit' => 90.0, 'cost' => 2.95],
            ['limit' => INF,  'cost' => 0.0],
        ]);

        $this->offers = [new BuyOneGetOneHalfPrice('R01')];
        
        $this->basket = new Basket($this->products, $this->deliveryRules, $this->offers);
    }

    public function test_add_single_product(): void
    {
        $this->basket->add('B01');
        $this->assertEquals(12.90, $this->basket->total()); // 7.95 + 4.95 delivery
    }

    public function test_add_multiple_products(): void
    {
        $this->basket->add('B01');
        $this->basket->add('G01');
        $this->assertEquals(37.85, $this->basket->total()); // 7.95 + 24.95 + 4.95 delivery
    }

    public function test_add_red_widgets_with_offer(): void
    {
        $this->basket->add('R01');
        $this->basket->add('R01');
        
        // Calculate the price of two widgets with the offer
        $fullPrice = 32.95;
        $halfPrice = floor($fullPrice / 2 * 100) / 100; // 16.47
        $subtotal = $fullPrice + $halfPrice; // 32.95 + 16.47 = 49.42
        $deliveryCost = 4.95;
        $expected = round($subtotal + $deliveryCost, 2); // round(49.42 + 4.95, 2) = 54.37
        
        $this->assertEquals($expected, $this->basket->total());
    }
    public function test_add_invalid_product(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->basket->add('INVALID');
    }

    public function test_free_delivery_threshold(): void
    {
        $this->basket->add('R01');
        $this->basket->add('R01');
        $this->basket->add('G01');
        $this->basket->add('G01');
        $expected = (32.95 + floor(32.95 / 2 * 100) / 100) + (24.95 * 2); // Products with offer, no delivery cost
        $this->assertEquals($expected, $this->basket->total());
    }
} 