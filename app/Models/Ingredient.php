<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Ingredient extends Model
{
    use HasFactory;

    public function recipe_ingredients():HasMany{
        return $this->hasMany(RecipeIngredient::class);
    }

    public function recipes():BelongsToMany{
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients');
    }
}
