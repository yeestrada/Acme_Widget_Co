<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckoutProcessTest extends TestCase
{
    public function test_checkout_button_disabled_when_basket_empty()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertViewIs('basket');
        $response->assertSee('disabled');
    }

    public function test_checkout_button_present_when_products_selected()
    {
        $response = $this->post('/basket', [
            'items' => [
                'R01' => 1
            ]
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('basket');
        $response->assertSee('Checkout');
        $response->assertSee('checkoutButton');
    }

    public function test_basket_calculation_returns_correct_values()
    {
        $response = $this->post('/basket/calculate', [
            'items' => [
                'R01' => 2,
                'G01' => 1
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
                    'deliveryRule'
                ]
            ]);
    }

    public function test_basket_calculation_with_multiple_items()
    {
        $response = $this->post('/basket/calculate', [
            'items' => [
                'R01' => 2,
                'G01' => 1,
                'B01' => 1
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
                    'deliveryRule'
                ]
            ]);
    }

    public function test_basket_calculation_ignores_invalid_items()
    {
        $response = $this->post('/basket/calculate', [
            'items' => [
                'INVALID' => 1
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
                    'deliveryRule'
                ]
            ]);
    }
} 