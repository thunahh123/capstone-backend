<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasurementUnit extends Model
{
    use HasFactory;
    

    public function recipe_ingredients():HasMany{
        return $this->hasMany(RecipeIngredient::class,'unit_id');
    }
}
