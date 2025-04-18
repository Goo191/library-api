<?php

namespace App\Http\Controllers;

use App\Models\books;
use App\Models\categories;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="عرض قائمة التصنيفات",
     *     description="عرض قائمة بجميع التصنيفات المتاحة",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="قائمة التصنيفات",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="الإدارة")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح")
     * )
     */
    public function listCategories()
    {
        $categories = categories::all(['id', 'name']);
        
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{name}/books",
     *     summary="عرض الكتب حسب التصنيف",
     *     description="عرض قائمة الكتب في التصنيف المحدد",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         description="اسم التصنيف (مثال: الإدارة)",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="قائمة الكتب في التصنيف المحدد",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Book"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح"),
     *     @OA\Response(response=404, description="التصنيف غير موجود")
     * )
     */
    public function getBooksByCategory($name)
    {
        $books = books::whereHas('category', function($q) use ($name) {
            $q->where('name', $name);
        })->with(['category', 'authors'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $books
        ]);
    }
} 