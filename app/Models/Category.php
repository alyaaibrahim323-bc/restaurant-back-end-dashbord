<?php

// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id','image','description','description_ar'];
 public function getRouteKeyName()
    {
        return 'id'; // استخدام slug بدلاً من id في الروت
    }

    // العلاقة مع التصنيفات الفرعية
    public function children() {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // العلاقة مع التصنيف الأب
    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // العلاقة مع المنتجات
    public function products() {
        return $this->hasMany(Product::class);
    }

    public function product()
{
    return $this->hasMany(Product::class, 'category_id');
}

}
