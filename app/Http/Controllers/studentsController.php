<?php

namespace App\Http\Controllers;

use App\Models\students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Students",
 *     description="إدارة بيانات الطلاب"
 * )
 */
class studentsController extends Controller
{
    /**
     * @OA\Put(
     *     path="/api/students/{id}",
     *     summary="تحديث صورة الطالب",
     *     description="يتم تحديث صورة الطالب عبر إرسال مسار الصورة الجديد.",
     *     tags={"Students"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="معرف الطالب المطلوب تحديث صورته",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"image"},
     *             @OA\Property(property="image", type="string", example="students/image.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="تم تحديث الصورة بنجاح!",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Image updated successfully!"),
     *             @OA\Property(property="student", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, students $student)
    {
        $request->validate([
            'image' => 'required|string'
        ]);

        $student->update([
            'image' => $request->image
        ]);

        return response()->json([
            'message' => 'Image updated successfully!',
            'student' => $student
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/students/{id}",
     *     summary="عرض بيانات الطالب",
     *     description="إرجاع بيانات الطالب بما في ذلك الصورة المحفوظة.",
     *     tags={"Students"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="معرف الطالب المطلوب عرضه",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="تم جلب بيانات الطالب بنجاح.",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="يوسف طارق"),
     *             @OA\Property(property="student_id", type="string", example="123456"),
     *             @OA\Property(property="image", type="string", example="http://127.0.0.1:8000/storage/students/image.jpg")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $student = students::find($id);
        
        if (!$student) {
            return response()->json([
                'message' => 'الطالب غير موجود'
            ], 404);
        }

        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'student_id' => $student->student_id,
            'image' => $student->image ? asset('storage/' . $student->image) : null
        ]);
    }
}
