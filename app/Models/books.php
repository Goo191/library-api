<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class books extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['title', 'quantity', 'publish_year', 'category_id', 'qr_code', 'file'];
    
    // Set default value for qr_code if not provided
    protected $attributes = [
        'qr_code' => null
    ];

    public function category()
    {
        return $this->belongsTo(categories::class, 'category_id');
    }

    public function authors()
    {
        return $this->belongsToMany(authors::class, 'author_book', 'book_id', 'author_id');
    }

    public function students()
    {
        return $this->belongsToMany(students::class, 'book_student', 'book_id', 'student_id');
    }

    public function borrows()
    {
        return $this->hasMany(borrows::class);
    }

    public function getCategoryImageAttribute()
    {
        return $this->category ? $this->category->image_url : null;
    }
}
