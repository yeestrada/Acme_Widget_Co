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
        // Sort rules by limit from lowest to highest
        usort($rules, function($a, $b) {
            return $a['limit'] <=> $b['limit'];
        });
        
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
     * Get the applied delivery rule
     * @param float $subtotal
     * @return array|null
     */
    public function getAppliedRule(float $subtotal): ?array
    {
        // Sort rules by limit from lowest to highest
        $sortedRules = $this->rules;
        usort($sortedRules, function($a, $b) {
            return $a['limit'] <=> $b['limit'];
        });

        $minLimit = min(array_column($sortedRules, 'limit'));        
        foreach ($sortedRules as $rule) {
            if ($subtotal < $rule['limit']) {
                $message = null;
                if ($rule['cost'] <= 0) {
                    $message = "Free delivery";
                } elseif ($rule['limit'] != $minLimit) {
                    $message = "Delivery discount applied";
                }
                
                return [
                    'limit' => $rule['limit'],
                    'cost' => $rule['cost'],
                    'message' => $message
                ];
            }
        }
        return null;
    }
}
