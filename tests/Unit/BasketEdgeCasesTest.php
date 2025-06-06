<?php

namespace Tests\Unit;

use App\Services\Basket\Product;
use App\Services\Basket\Basket;
use App\Services\Basket\DeliveryRules;
use App\Services\Basket\Offers\BuyOneGetOneHalfPrice;
use Tests\TestCase;

class BasketEdgeCasesTest extends TestCase
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
            ['limit' => PHP_FLOAT_MAX,  'cost' => 0.0],
        ]);

        $this->offers = [new BuyOneGetOneHalfPrice('R01')];
        
        $this->basket = new Basket($this->products, $this->deliveryRules, $this->offers);
    }

    public function test_empty_basket_returns_zero_subtotal()
    {
        $this->assertEquals(0, $this->basket->getSubtotal());
        $this->assertEquals(0, $this->basket->getDiscounts());
        $this->assertEquals(4.95, $this->basket->getDeliveryCost());
        $this->assertEquals(4.95, $this->basket->total());
    }

    public function test_basket_with_multiple_offers()
    {
        // Add 4 red widgets to test multiple pairs
        $this->basket->add('R01');
        $this->basket->add('R01');
        $this->basket->add('R01');
        $this->basket->add('R01');

        // Calculations:
        // R01 (first): 32.95
        // R01 (second): 32.95
        // R01 (third): 32.95
        // R01 (fourth): 32.95
        // Total antes de descuentos: 32.95 * 4 = 131.80
        $this->assertEquals(131.80, $this->basket->getSubtotal());

        // Discounts:
        // Second R01: 32.95 - 16.47 = 16.48
        // Fourth R01: 32.95 - 16.47 = 16.48
        // Total discounts: 32.96
        $this->assertEquals(32.96, $this->basket->getDiscounts());
    }

    public function test_basket_with_mixed_products_and_offers()
    {
        // Add products that don't have offers
        $this->basket->add('G01');  // 24.95
        $this->basket->add('B01');  // 7.95
        
        // Add products with offers
        $this->basket->add('R01');  // 32.95
        $this->basket->add('R01');  // 32.95

        // Calculations:
        // G01: 24.95
        // B01: 7.95
        // R01 (first): 32.95
        // R01 (second): 32.95
        // Total without discounts: 24.95 + 7.95 + 32.95 + 32.95 = 98.80
        $this->assertEquals(98.80, $this->basket->getSubtotal());
        
        // Discount: 32.95 - 16.47 = 16.48
        $this->assertEquals(16.48, $this->basket->getDiscounts());
    }

    public function test_basket_reset_after_calculation()
    {
        $this->basket->add('R01');
        $this->basket->add('R01');
        
        // First calculation
        $firstTotal = $this->basket->total();
        
        // Second calculation should give same result
        $secondTotal = $this->basket->total();
        
        $this->assertEquals($firstTotal, $secondTotal);
    }

    public function test_product_discounts_array_structure()
    {
        $this->basket->add('R01');
        $this->basket->add('R01');
        
        $discounts = $this->basket->getProductDiscounts();
        
        $this->assertArrayHasKey('R01', $discounts);
        $this->assertArrayHasKey('original', $discounts['R01']);
        $this->assertArrayHasKey('discounted', $discounts['R01']);
        $this->assertArrayHasKey('savings', $discounts['R01']);
    }
} 