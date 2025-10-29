<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->recipes->isEmpty()) {
            return 0;
        }

        $minStock = PHP_INT_MAX;

        foreach ($this->recipes as $recipe) {
            $rawMaterial = $recipe->rawMaterial;
            $productsUsingMaterial = Product::with('recipes')
                ->whereHas('recipes', function ($query) use ($rawMaterial) {
                    $query->where('raw_material_id', $rawMaterial->id);
                })
                ->where('is_available', true)
                ->get();

            $totalQuantityPerBatch = 0;
            foreach ($productsUsingMaterial as $product) {
                $recipeForThisProduct = $product->recipes
                    ->where('raw_material_id', $rawMaterial->id)
                    ->first();

                if ($recipeForThisProduct) {
                    $totalQuantityPerBatch += $recipeForThisProduct->quantity;
                }
            }

            if ($totalQuantityPerBatch > $rawMaterial->stock) {
                return 0;
            }

            $possibleBatches = floor($rawMaterial->stock / $totalQuantityPerBatch);
            $minStock = min($minStock, $possibleBatches);
        }

        return $minStock === PHP_INT_MAX ? 0 : (int) $minStock;
    }

    public function canBeMade(int $quantity = 1): bool
    {
        foreach ($this->recipes as $recipe) {
            $required = $recipe->quantity * $quantity;
            if ($recipe->rawMaterial->stock < $required) {
                return false;
            }
        }
        return true;
    }

    public function reduceStock(int $quantity = 1): void
    {
        foreach ($this->recipes as $recipe) {
            $rawMaterial = $recipe->rawMaterial;
            $rawMaterial->stock -= ($recipe->quantity * $quantity);
            $rawMaterial->save();
        }
    }
}
