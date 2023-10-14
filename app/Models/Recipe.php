<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    public function comments():HasMany{
        return $this->hasMany(Comment::class);
    }

    public function ratings():HasMany{
        return $this->hasMany(Rating::class);
    }

    public function saved_recipes():HasMany{
        return $this->hasMany(SavedRecipe::class);
    }

    public function recipe_ingredients():HasMany{
        return $this->hasMany(RecipeIngredient::class);
    }

    public function user():BelongsTo{
        return $this->belongsTo(User::class,'author_id');
    }

    public function ingredients():BelongsToMany{
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients');
    }
}
