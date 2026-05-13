<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\StokGudang;
use App\Models\KitchenStock;

class RecipeService
{
    public function calculateHPP($recipeId)
    {
        $recipe = Recipe::with('items')->findOrFail($recipeId);

        $totalCost = 0;

        foreach ($recipe->items as $item) {
            $ingredient = $item->ingredient_type === 'gudang'
                ? StokGudang::find($item->ingredient_id)
                : KitchenStock::find($item->ingredient_id);

            if ($ingredient) {
                $pricePerUnit = $item->ingredient_type === 'gudang'
                    ? $ingredient->price_per_unit
                    : 0;

                $cost = $item->quantity * $pricePerUnit;
                $item->update(['cost' => $cost]);
                $totalCost += $cost;
            }
        }

        $recipe->update(['hpp' => $totalCost]);

        return $totalCost;
    }

    public function calculateProfitability($recipeId)
    {
        $recipe = Recipe::findOrFail($recipeId);

        $hpp = $recipe->hpp;
        $sellingPrice = $recipe->selling_price;

        if ($sellingPrice <= 0) {
            return [
                'hpp' => $hpp,
                'selling_price' => $sellingPrice,
                'profit' => 0,
                'margin' => 0,
                'roi' => 0,
            ];
        }

        $profit = $sellingPrice - $hpp;
        $margin = ($profit / $sellingPrice) * 100;
        $roi = $hpp > 0 ? ($profit / $hpp) * 100 : 0;

        return [
            'hpp' => $hpp,
            'selling_price' => $sellingPrice,
            'profit' => $profit,
            'margin' => round($margin, 2),
            'roi' => round($roi, 2),
        ];
    }
}
