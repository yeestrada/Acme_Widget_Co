<?php

/*
|--------------------------------------------------------------------------
| Delivery rules
|--------------------------------------------------------------------------
|
| Each entry represents a delivery rule.
| 'limit' is the limit of the rule.
| 'cost' is the cost of the rule.
*/

return [
    // Rules ordered by ascending limit
    'rules' => [
        ['limit' => 50.0, 'cost' => 4.95],
        ['limit' => 90.0, 'cost' => 2.95],
        ['limit' => INF,  'cost' => 0.0],
    ],
];
