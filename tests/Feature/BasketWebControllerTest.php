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
            'items' => [
                'R01' => 2,
                'G01' => 1
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'subtotal',
            'discounts',
            'deliveryCost',
            'deliveryRule',
            'total'
        ]);
    }

    public function test_calculate_ajax_handles_invalid_products()
    {
        $response = $this->post('/basket/calculate', [
            'items' => [
                'INVALID' => 1
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'subtotal' => 0,
            'discounts' => 0,
            'deliveryCost' => 4.95,
            'total' => 4.95
        ]);
    }

    public function test_calculate_ajax_handles_negative_quantities()
    {
        $response = $this->post('/basket/calculate', [
            'items' => [
                'R01' => -1
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'subtotal' => 0,
            'discounts' => 0,
            'deliveryCost' => 4.95,
            'total' => 4.95
        ]);
    }

    public function test_calculate_ajax_handles_empty_basket()
    {
        $response = $this->post('/basket/calculate', [
            'items' => []
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'subtotal' => 0,
            'discounts' => 0,
            'deliveryCost' => 4.95,
            'total' => 4.95
        ]);
    }

    public function test_calculate_ajax_applies_red_widget_offer()
    {
        $response = $this->post('/basket/calculate', [
            'items' => [
                'R01' => 2
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        // Subtotal should be 49.42 (32.95 + 16.48)
        $this->assertEquals(49.42, $data['subtotal']);
        // Discount should be 16.48 (32.95 / 2)
        $this->assertEquals(16.48, $data['discounts']);
    }

    public function test_calculate_ajax_applies_delivery_rules()
    {
        // Test standard delivery (subtotal < 50)
        $response = $this->post('/basket/calculate', [
            'items' => [
                'G01' => 1  // 24.95
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(4.95, $data['deliveryCost']);

        // Test premium delivery (50 <= subtotal < 90)
        $response = $this->post('/basket/calculate', [
            'items' => [
                'R01' => 2,  // 32.95 * 2 = 65.90
                'G01' => 1   // 24.95
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2.95, $data['deliveryCost']);

        // Test free delivery (subtotal >= 90)
        $response = $this->post('/basket/calculate', [
            'items' => [
                'R01' => 4,  // 32.95 * 4 = 131.80 (con oferta: 98.85)
                'G01' => 1   // 24.95
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['deliveryCost']);
    }
} 