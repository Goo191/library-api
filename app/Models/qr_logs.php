<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class qr_logs extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'check_in',
        'check_out'
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime'
    ];

    public function student()
    {
        return $this->belongsTo(students::class, 'student_id');
    }
}
