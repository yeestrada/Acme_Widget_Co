<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Basket\Product;
use App\Services\Basket\Basket;
use App\Services\Basket\DeliveryRules;
use App\Services\Basket\Offers\BuyOneRedHalfPrice;

class TestBasket extends Command
{
    protected $signature = 'basket:test';
    protected $description = 'Prueba la lógica del carrito';

    public function handle()
    {
        $products = [
            'R01' => new Product('R01', 'Red Widget', 32.95),
            'G01' => new Product('G01', 'Green Widget', 24.95),
            'B01' => new Product('B01', 'Blue Widget', 7.95),
        ];

        $rules = new DeliveryRules([
            ['limit' => 50.0, 'cost' => 4.95],
            ['limit' => 90.0, 'cost' => 2.95],
            ['limit' => INF,  'cost' => 0.0],
        ]);

        $offers = [new BuyOneRedHalfPrice()];

        $basket = new Basket($products, $rules, $offers);

        $basket->add('R01');
        $basket->add('R01');

        $this->info("Total: $" . number_format($basket->total(), 2));
    }
}
