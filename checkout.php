<?php

class Checkout
{
    private $pricingRules;
    private $items;

    public function __construct($pricingRules = [])
    {
        $this->pricingRules = $pricingRules;
        $this->items = [];
    }

    public function scan($item)
    {
        if (!isset($this->items[$item])) {
            $this->items[$item] = 0;
        }
        $this->items[$item]++;
    }

    public function total()
    {
        $total = 0;
        $itemCounts = $this->items;

        foreach ($this->pricingRules as $item => $rules) {
            $quantity = isset($itemCounts[$item]) ? $itemCounts[$item] : 0;

            if ($quantity > 0) {
                if (isset($rules['special'])) {
                    switch ($rules['special']['type']) {
                        case 'multipriced':
                            $total += intval($quantity / $rules['special']['quantity']) * $rules['special']['price'];
                            $quantity %= $rules['special']['quantity'];
                            $total += $quantity * $rules['price'];
                            break;
                        case 'buy_n_get_1_free':
                            $total += intval($quantity / ($rules['special']['quantity'] + 1)) * $rules['special']['quantity'] * $rules['price'];
                            $quantity %= ($rules['special']['quantity'] + 1);
                            $total += $quantity * $rules['price'];
                            break;
                        case 'meal_deal':
                            $pair = $rules['special']['pair'];
                            if (isset($itemCounts[$pair]) && $itemCounts[$pair] > 0) {
                                $pairQuantity = $itemCounts[$pair];
                                $mealDealQuantity = min($quantity, $pairQuantity);
                                $total += $mealDealQuantity * $rules['special']['price'];
                                $quantity -= $mealDealQuantity;
                                $itemCounts[$pair] -= $mealDealQuantity;
                            }
                            $total += $quantity * $rules['price'];
                            break;
                    }
                } else {
                    $total += $quantity * $rules['price'];
                }
            }
        }

        return number_format($total / 100, 2); 
    }

    public function getItems()
    {
        return $this->items;
    }
}
?>
