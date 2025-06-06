<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BasketWebControllerTest extends TestCase
{
    public function test_calculate_returns_correct_view()
    {
        $response = $this->post('/basket', [
            'items' => [
                'R01' => 2,
                'G01' => 1
            ]
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('basket');
        $response->assertViewHas([
            'products',
            'total',
            'subtotal',
            'discounts',
            'deliveryCost',
            'productDiscounts',
            'selected',
            'offers',
            'offerTexts'
        ]);
    }

    public function test_calculate_ajax_returns_correct_json()
    {
        $response = $this->post('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 2],
                ['code' => 'G01', 'quantity' => 1]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'subtotal',
                'discounts',
                'deliveryCost',
                'deliveryRule',
                'total',
                'appliedOffers'
            ]
        ]);
    }

    public function test_calculate_ajax_handles_invalid_products()
    {
        $response = $this->post('/basket/calculate', [
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

    public function test_calculate_ajax_handles_negative_quantities()
    {
        $response = $this->post('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => -1]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'subtotal' => 0,
                'discounts' => 0,
                'deliveryCost' => 4.95,
                'total' => 4.95,
                'deliveryRule' => [
                    'limit' => 50,
                    'cost' => 4.95,
                    'message' => null
                ],
                'appliedOffers' => []
            ]
        ]);
    }

    public function test_calculate_ajax_handles_empty_basket()
    {
        $response = $this->post('/basket/calculate', [
            'products' => []
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'subtotal' => 0,
                'discounts' => 0,
                'deliveryCost' => 4.95,
                'total' => 4.95,
                'deliveryRule' => [
                    'limit' => 50,
                    'cost' => 4.95,
                    'message' => null
                ],
                'appliedOffers' => []
            ]
        ]);
    }

    public function test_calculate_ajax_applies_red_widget_offer()
    {
        $response = $this->post('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 2]
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verificar la estructura de la respuesta
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('subtotal', $data['data']);
        $this->assertArrayHasKey('discounts', $data['data']);

        // Verificar los valores
        $this->assertEquals(65.90, $data['data']['subtotal']);  //Subtotal without discounts
        $this->assertEquals(16.48, $data['data']['discounts']); // Discount applied
    }

    public function test_calculate_ajax_applies_delivery_rules()
    {
        // Test standard delivery (subtotal < 50)
        $response = $this->post('/basket/calculate', [
            'products' => [
                ['code' => 'G01', 'quantity' => 1]  // 24.95
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(4.95, $data['data']['deliveryCost']);

        // Test premium delivery (50 <= subtotal < 90)
        $response = $this->post('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 2],  // 32.95 * 2 = 65.90 (with offer: 49.42)
                ['code' => 'G01', 'quantity' => 1]   // 24.95
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(2.95, $data['data']['deliveryCost']);

        // Test free delivery (subtotal >= 90)
        $response = $this->post('/basket/calculate', [
            'products' => [
                ['code' => 'R01', 'quantity' => 3],  // 32.95 * 3 = 98.85 (with offer: 82.38)
                ['code' => 'G01', 'quantity' => 1]   // 24.95
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(0, $data['data']['deliveryCost']);
    }
} 