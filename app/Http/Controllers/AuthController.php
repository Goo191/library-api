<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\students;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="تسجيل الدخول للطالب",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "student_id"},
     *             @OA\Property(property="name", type="string", example=" يوسف طارق احمد"),
     *             @OA\Property(property="student_id", type="numeric", example=15901)
     *         )
     *     ),
     *     @OA\Response(response=200, description="تم تسجيل الدخول بنجاح."),
     *     @OA\Response(response=401, description="بيانات الطالب غير صحيحة.")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'student_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = students::where('name', $request->name)
            ->where('student_id', $request->student_id)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير صحيحة'], 401);
        }

        $token = $student->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'student' => $student
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="تسجيل خروج الطالب",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="تم تسجيل الخروج بنجاح."),
     *     @OA\Response(response=401, description="غير مصرح لك.")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="الحصول على بيانات الطالب الحالي",
     *     description="إرجاع بيانات الطالب المسجل دخوله حالياً",
     *     tags={"Students"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="بيانات الطالب",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="يوسف طارق احمد"),
     *             @OA\Property(property="student_id", type="integer", example=15901),
     *             @OA\Property(property="image", type="string", example="http://localhost/storage/students/image.jpg")
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح لك")
     * )
     */
    public function getCurrentStudent(Request $request)
    {
        $student = $request->user();
        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'student_id' => $student->student_id,
            'image' => $student->image ? asset('storage/' . $student->image) : null
        ]);
    }
}
