<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;
    protected $hidden = ['pw'];

    public function recipes():HasMany{
        return $this->hasMany(Recipe::class, 'author_id');
    }

    public function comments():HasMany{
        return $this->hasMany(Comment::class, 'author_id');
    }

    public function ratings():HasMany{
        return $this->hasMany(Rating::class);
    }

    public function saved_recipes():HasMany{
        return $this->hasMany(SavedRecipe::class);
    }

    public function sessions():HasMany{
        return $this->hasMany(Session::class);
    }
}
