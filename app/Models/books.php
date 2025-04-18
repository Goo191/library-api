<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class books extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['title', 'quantity', 'publish_year', 'category_id', 'qr_code','file'];

    public function category()
    {
        return $this->belongsTo(categories::class);
    }

    public function authors()
    {
        return $this->belongsToMany(authors::class, 'author_book', 'book_id', 'author_id');
    }

    public function students()
    {
        return $this->belongsToMany(students::class, 'book_student');
    }

    public function borrows()
    {
        return $this->hasMany(borrows::class);
    }
}
