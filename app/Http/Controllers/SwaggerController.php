<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SwaggerController extends Controller
{
    public function index()
    {
        return view('swagger.index');
    }

    public function docs()
    {
        return response()->file(storage_path('api-docs/api-docs.json'));
    }
} 