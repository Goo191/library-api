<?php

namespace App\Http\Controllers;

use App\Models\qr_logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;


class QrController extends Controller
{
    public function generateQrOnDemand()
{
    // نفس كود توليد الكيو آر الموجود في الكوماند
    $content = now()->format('Y-m-d H:i:s');
    Storage::put('qr/current.txt', $content);

    $qrCode = new \Endroid\QrCode\QrCode(
        data: $content,
        size: 300,
        margin: 10
    );
    $writer = new \Endroid\QrCode\Writer\PngWriter();
    $result = $writer->write($qrCode);

    $directory = storage_path('app/public/qrcodes');
    if (!File::exists($directory)) {
        File::makeDirectory($directory, 0755, true);
    }
    file_put_contents($directory.'/library_qr_code.png', $result->getString());

    return response()->json(['status' => 'ok']);
}
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
              // إزالة المسافات الزائدة من الكود المرسل
            $qrContent = trim($request->qr_content);
        // التحقق من صحة محتوى QR
        $validQrContent = Storage::get('qr/current.txt');

        if ($request->qr_content !== $validQrContent) {
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
    // إزالة المسافات الزائدة من الكود المرسل
    $qrContent = trim($request->qr_content);
        // التحقق من صحة محتوى QR
        $validQrContent = Storage::get('qr/current.txt');

        if ($request->qr_content !== $validQrContent) {
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
    /**
 * @OA\Get(
 *     path="/api/qr-status",
 *     summary="جلب حالة تسجيل دخول الطالب في المكتبة",
 *     tags={"Library Access"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="الحالة تم إرجاعها بنجاح"),
 *     @OA\Response(response=401, description="غير مصرح له")
 * )
 */
public function checkStatus()
{
    $activeLog = qr_logs::where('student_id', auth()->user()->id)
        ->whereNull('check_out')
        ->first();

    if ($activeLog) {
        return response()->json([
            'status' => 'in',
            'message' => 'الطالب مسجل دخول حالياً في المكتبة',
            'log' => $activeLog
        ]);
    }

    return response()->json([
        'status' => 'out',
        'message' => 'الطالب غير مسجل دخول في المكتبة حالياً'
    ]);
}

    
} 