<?php

namespace Tests\Unit;

use App\Services\Basket\DeliveryRules;
use PHPUnit\Framework\TestCase;

class DeliveryRulesTest extends TestCase
{
    private DeliveryRules $deliveryRules;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->deliveryRules = new DeliveryRules([
            ['limit' => 50.0, 'cost' => 4.95],
            ['limit' => 90.0, 'cost' => 2.95],
            ['limit' => INF,  'cost' => 0.0],
        ]);
    }

    public function test_delivery_cost_for_subtotal_below_50(): void
    {
        $this->assertEquals(4.95, $this->deliveryRules->getCost(49.99));
    }

    public function test_delivery_cost_for_subtotal_between_50_and_90(): void
    {
        $this->assertEquals(2.95, $this->deliveryRules->getCost(75.00));
    }

    public function test_delivery_cost_for_subtotal_above_90(): void
    {
        $this->assertEquals(0.0, $this->deliveryRules->getCost(100.00));
    }
} 