<?php

namespace App\Services\Basket;

/**
 * DeliveryRules class
 * This class is used to calculate the delivery cost based on the subtotal of the basket
 */
class DeliveryRules
{
    protected array $rules = [];

    /**
     * Create a new instance of the delivery rules
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        // Example: [ ['limit' => 50.0, 'cost' => 4.95], ... ]
        $this->rules = $rules;
    }

    /**
     * Get the delivery cost based on the subtotal of the basket
     * @param float $subtotal
     * @return float
     */
    public function getCost(float $subtotal): float
    {
        // Check if the subtotal is less than the limit of the first rule
        foreach ($this->rules as $rule) {
            if ($subtotal < $rule['limit']) {
                return $rule['cost'];
            }
        }

        // If no rule applies, delivery is free
        return 0.0;
    }
}
