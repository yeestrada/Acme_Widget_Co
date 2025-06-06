<?php

use App\Services\Basket\Offers\BuyOneGetOneHalfPrice;

/*
|--------------------------------------------------------------------------
| Active offers list
|--------------------------------------------------------------------------
|
| Each entry represents an offer that is applied to certain products.
| 'class' defines the offer class.
| 'products' is an array with the product codes to which the offer is applied.
|
*/

return [
    'buy_one_get_half_price' => [
        'class' => BuyOneGetOneHalfPrice::class,
        'products' => ['R01'],
    ],
];