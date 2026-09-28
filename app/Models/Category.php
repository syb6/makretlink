<?php

namespace App\Models;

use App\Services\ImageLibrary;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'status',
    ];

    /**
     * URL to the category's picture straight from its `image` column
     * (a path inside public/images/placeholders/category-images/).
     */
    public function getImageUrlAttribute(): string
    {
        return ImageLibrary::url('category', $this->image)
            ?? asset('images/placeholders/general.png');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
