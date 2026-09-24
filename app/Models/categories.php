<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class categories extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'image'];

    public function books()
    {
        return $this->hasMany(books::class, 'category_id');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        // تنظيف المسار من storage/ في البداية إذا وجد
        $cleanPath = str_replace('storage/', '', $this->image);
        // إزالة المسافات الزائدة
        $cleanPath = trim($cleanPath);
        return asset('storage/' . $cleanPath);
    }
}