<?php

namespace App\Http\Controllers;

use App\Models\qr_logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QrController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/qr-login",
     *     summary="تسجيل دخول الطالب للمكتبة عن طريق QR",
     *     tags={"Library Access"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qr_content"},
     *             @OA\Property(property="qr_content", type="string", example="library_qr_code")
     *         )
     *     ),
     *     @OA\Response(response=200, description="تم تسجيل الدخول بنجاح"),
     *     @OA\Response(response=400, description="رمز QR غير صالح"),
     *     @OA\Response(response=401, description="غير مصرح له")
     * )
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'qr_content' => 'required|string'
        ]);

        // التحقق من صحة محتوى QR
        if ($request->qr_content !== 'library_qr_code') {
            return response()->json([
                'message' => 'رمز QR غير صالح'
            ], 400);
        }

        // التحقق من عدم وجود تسجيل دخول نشط
        $activeLog = qr_logs::where('student_id', auth()->user()->id)
            ->whereNull('check_out')
            ->first();

        if ($activeLog) {
            return response()->json([
                'message' => 'لديك بالفعل تسجيل دخول نشط في المكتبة'
            ], 400);
        }

        // إنشاء سجل دخول جديد
        $log = qr_logs::create([
            'student_id' => auth()->user()->id,
            'check_in' => now(),
            'check_out' => null
        ]);

        return response()->json([
            'message' => 'تم تسجيل دخولك للمكتبة بنجاح',
            'check_in' => true,
            'log' => $log
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/qr-logout",
     *     summary="تسجيل خروج الطالب من المكتبة",
     *     tags={"Library Access"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qr_content"},
     *             @OA\Property(property="qr_content", type="string", example="library_qr_code")
     *         )
     *     ),
     *     @OA\Response(response=200, description="تم تسجيل الخروج بنجاح"),
     *     @OA\Response(response=400, description="رمز QR غير صالح أو لا يوجد تسجيل دخول نشط"),
     *     @OA\Response(response=401, description="غير مصرح له")
     * )
     */
    public function checkOut(Request $request)
    {
        $request->validate([
            'qr_content' => 'required|string'
        ]);

        // التحقق من صحة محتوى QR
        if ($request->qr_content !== 'library_qr_code') {
            return response()->json([
                'message' => 'رمز QR غير صالح'
            ], 400);
        }

        // البحث عن آخر تسجيل دخول نشط
        $activeLog = qr_logs::where('student_id', auth()->user()->id)
            ->whereNull('check_out')
            ->first();

        if (!$activeLog) {
            return response()->json([
                'message' => 'لا يوجد تسجيل دخول نشط في المكتبة'
            ], 400);
        }

        // تحديث وقت الخروج
        $activeLog->update([
            'check_out' => now()
        ]);

        return response()->json([
            'message' => 'تم تسجيل خروجك من المكتبة بنجاح',
            'check_out' => true,
            'log' => $activeLog
        ]);
    }
} 