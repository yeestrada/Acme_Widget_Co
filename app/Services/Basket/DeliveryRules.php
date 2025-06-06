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

    /**
     * Get the delivery rule that is being applied
     * @param float $subtotal
     * @return array|null
     */
    public function getAppliedRule(float $subtotal): ?array
    {
        $maxCost = max(array_column($this->rules, 'cost'));
        
        foreach ($this->rules as $rule) {
            if ($subtotal < $rule['limit']) {
                // If the cost is 0, show "Free delivery"
                if ($rule['cost'] === 0.0) {
                    return [
                        'limit' => $rule['limit'],
                        'cost' => $rule['cost'],
                        'message' => "Free delivery"
                    ];
                }
                
                // If the cost is less than the maximum, show the discount message
                return [
                    'limit' => $rule['limit'],
                    'cost' => $rule['cost'],
                    'message' => $rule['cost'] < $maxCost ? "Delivery discount applied" : null
                ];
            }
        }

        return null;
    }
}
