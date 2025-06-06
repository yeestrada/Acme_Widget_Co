<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BasketApiTest extends TestCase
{
    public function test_api_calculates_subtotal()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 1],
                ['code' => 'G01', 'quantity' => 1]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'subtotal',
                    'deliveryCost',
                    'total',
                    'discounts',
                    'deliveryRule',
                    'appliedOffers'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'subtotal' => 57.90
                ]
            ]);
    }

    public function test_api_validates_invalid_product()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => [
                ['code' => 'INVALID', 'quantity' => 1]
            ]
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Product not valid: INVALID'
            ]);
    }

    public function test_api_validates_negative_quantity()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => -1]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'subtotal' => 0,
                    'discounts' => 0,
                    'deliveryCost' => 4.95,
                    'deliveryRule' => [
                        'limit' => 50,
                        'cost' => 4.95,
                        'message' => null
                    ],
                    'total' => 4.95,
                    'appliedOffers' => []
                ]
            ]);
    }

    public function test_api_returns_correct_delivery_cost()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => [
                ['code' => 'G01', 'quantity' => 1]  // 24.95
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deliveryCost' => 4.95,
                    'deliveryRule' => [
                        'limit' => 50,
                        'cost' => 4.95,
                        'message' => null
                    ]
                ]
            ]);
    }

    public function test_api_returns_correct_discounts()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 2]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'subtotal',
                    'discounts',
                    'deliveryCost',
                    'deliveryRule',
                    'total',
                    'appliedOffers'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'subtotal' => 65.90,
                    'discounts' => 16.48
                ]
            ]);
    }

    public function test_api_returns_correct_delivery_rule_for_high_value()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 2],
                ['code' => 'G01', 'quantity' => 1]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deliveryRule' => [
                        'limit' => 90,
                        'cost' => 2.95,
                        'message' => 'Delivery discount applied'
                    ]
                ]
            ]);
    }

    public function test_api_returns_free_delivery_for_very_high_value()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 10]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deliveryRule' => [
                        'limit' => 1.7976931348623157E+308,
                        'cost' => 0,
                        'message' => 'Free delivery'
                    ]
                ]
            ]);
    }

    public function test_api_handles_multiple_products_with_offers()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 4],
                ['code' => 'G01', 'quantity' => 2],
                ['code' => 'B01', 'quantity' => 3]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'subtotal',
                    'discounts',
                    'deliveryCost',
                    'deliveryRule',
                    'total',
                    'appliedOffers'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'deliveryRule' => [
                        'limit' => 1.7976931348623157E+308,
                        'cost' => 0,
                        'message' => 'Free delivery'
                    ]
                ]
            ]);
    }

    public function test_api_handles_empty_basket()
    {
        $response = $this->postJson('/basket/calculate', [
            'products' => []
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'subtotal' => 0,
                    'discounts' => 0,
                    'deliveryCost' => 4.95,
                    'deliveryRule' => [
                        'limit' => 50,
                        'cost' => 4.95,
                        'message' => null
                    ],
                    'total' => 4.95,
                    'appliedOffers' => []
                ]
            ]);
    }
} 