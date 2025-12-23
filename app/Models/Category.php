<?php

// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id','image','description','description_ar'];
 public function getRouteKeyName()
    {
        return 'id';
    }


    public function children() {
        return $this->hasMany(Category::class, 'parent_id');
    }


    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }


    public function products() {
        return $this->hasMany(Product::class);
    }

    public function product()
{
    return $this->hasMany(Product::class, 'category_id');
}

}
