<?php

use App\Models\students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\studentsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="YouLibrary API",
 *     description="API documentation for the library system"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication endpoints"
 * )
 */

/**
 * @OA\Tag(
 *     name="Library",
 *     description="Library access endpoints"
 * )
 */

/**
 * @OA\Tag(
 *     name="Students",
 *     description="Student management endpoints"
 * )
 */

/**
 * @OA\Tag(
 *     name="Books",
 *     description="عمليات إدارة الكتب والاستعارة"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Book",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="عنوان الكتاب"),
 *     @OA\Property(property="quantity", type="integer", example=5),
 *     @OA\Property(property="category", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(property="authors", type="array", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string")
 *     ))
 * )
 */

/**
 * @OA\Get(
 *     path="/",
 *     summary="الصفحة الرئيسية",
 *     description="الصفحة الرئيسية للتطبيق",
 *     tags={"General"},
 *     @OA\Response(response=200, description="الصفحة الرئيسية")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/documentation",
 *     summary="عرض توثيق API",
 *     description="صفحة توثيق كاملة لجميع نقاط النهاية في النظام",
 *     tags={"Documentation"},
 *     @OA\Response(response=200, description="Swagger UI")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/login",
 *     summary="تسجيل الدخول",
 *     description="تسجيل دخول الطالب باستخدام الاسم ورقم الطالب",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "student_id"},
 *             @OA\Property(property="name", type="string", example="يوسف طارق احمد"),
 *             @OA\Property(property="student_id", type="integer", example=15901)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="تم تسجيل الدخول بنجاح",
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz"),
 *             @OA\Property(property="student", type="object", ref="#/components/schemas/Student")
 *         )
 *     ),
 *     @OA\Response(response=401, description="بيانات الدخول غير صحيحة")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/logout",
 *     summary="تسجيل الخروج",
 *     description="تسجيل خروج الطالب الحالي",
 *     tags={"Authentication"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="تم تسجيل الخروج بنجاح"),
 *     @OA\Response(response=401, description="غير مصرح لك")
 * )
 */

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

/**
 * @OA\Post(
 *     path="/api/qr-login",
 *     summary="تسجيل دخول الطالب للمكتبة عن طريق QR",
 *     description="تسجيل دخول الطالب للمكتبة باستخدام رمز QR",
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
 *     @OA\Response(response=400, description="رمز QR غير صالح أو لديك تسجيل دخول نشط"),
 *     @OA\Response(response=401, description="غير مصرح لك")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/qr-logout",
 *     summary="تسجيل خروج الطالب من المكتبة",
 *     description="تسجيل خروج الطالب من المكتبة باستخدام رمز QR",
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
 *     @OA\Response(response=401, description="غير مصرح لك")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Student",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="يوسف طارق احمد"),
 *     @OA\Property(property="student_id", type="integer", example=15901),
 *     @OA\Property(property="image", type="string", example="http://localhost/storage/students/image.jpg")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/books",
 *     summary="عرض قائمة الكتب",
 *     description="عرض قائمة الكتب مع إمكانية البحث والتصفية",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="category",
 *         in="query",
 *         description="اسم الفئة للتصفية",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="كلمة البحث في عنوان الكتاب",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="قائمة الكتب",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Book"))
 *         )
 *     ),
 *     @OA\Response(response=401, description="غير مصرح")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/books",
 *     summary="إضافة كتاب جديد",
 *     description="إضافة كتاب جديد إلى المكتبة",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title", "quantity", "category_id"},
 *             @OA\Property(property="title", type="string", example="عنوان الكتاب"),
 *             @OA\Property(property="quantity", type="integer", example=5),
 *             @OA\Property(property="publish_year", type="integer", example=2024),
 *             @OA\Property(property="category_id", type="integer", example=1),
 *             @OA\Property(property="file", type="string", nullable=true, example="path/to/file.pdf")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="تم إضافة الكتاب بنجاح",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="تم إضافة الكتاب بنجاح"),
 *             @OA\Property(property="book", type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="title", type="string"),
 *                 @OA\Property(property="quantity", type="integer"),
 *                 @OA\Property(property="publish_year", type="integer"),
 *                 @OA\Property(property="category_id", type="integer"),
 *                 @OA\Property(property="qr_code", type="string", nullable=true),
 *                 @OA\Property(property="file", type="string", nullable=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="غير مصرح"),
 *     @OA\Response(response=422, description="بيانات غير صالحة")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/books/borrow",
 *     summary="استعارة كتاب",
 *     description="استعارة كتاب عن طريق مسح QR code",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"qr_code"},
 *             @OA\Property(property="qr_code", type="string", example="book_123_qr")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="تم الاستعارة بنجاح",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="تم استعارة الكتاب بنجاح"),
 *             @OA\Property(property="borrow", type="object",
 *                 @OA\Property(property="book_title", type="string"),
 *                 @OA\Property(property="borrow_date", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=400, description="خطأ في البيانات أو الكتاب غير متوفر"),
 *     @OA\Response(response=401, description="غير مصرح"),
 *     @OA\Response(response=404, description="الكتاب غير موجود")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/books/return",
 *     summary="إرجاع كتاب",
 *     description="إرجاع كتاب عن طريق مسح QR code",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"qr_code"},
 *             @OA\Property(property="qr_code", type="string", example="book_123_qr")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="تم الإرجاع بنجاح",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="تم إرجاع الكتاب بنجاح"),
 *             @OA\Property(property="book_title", type="string")
 *         )
 *     ),
 *     @OA\Response(response=400, description="خطأ في البيانات"),
 *     @OA\Response(response=401, description="غير مصرح"),
 *     @OA\Response(response=404, description="الكتاب غير موجود أو لا يوجد استعارة نشطة")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/books/qr/{qrCode}",
 *     summary="البحث عن كتاب باستخدام QR",
 *     description="البحث عن معلومات كتاب باستخدام رمز QR",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="qrCode",
 *         in="path",
 *         description="رمز QR للكتاب",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="معلومات الكتاب",
 *         @OA\JsonContent(ref="#/components/schemas/Book")
 *     ),
 *     @OA\Response(response=404, description="الكتاب غير موجود"),
 *     @OA\Response(response=401, description="غير مصرح")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/books/with-qr",
 *     summary="عرض الكتب مع رموز QR",
 *     description="عرض قائمة الكتب مع روابط رموز QR الخاصة بها",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="قائمة الكتب مع رموز QR",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="title", type="string"),
 *                     @OA\Property(property="qr_code", type="string")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="غير مصرح")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/books/all",
 *     summary="عرض جميع الكتب",
 *     description="عرض قائمة بجميع الكتب في المكتبة",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="قائمة جميع الكتب",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Book"))
 *         )
 *     ),
 *     @OA\Response(response=401, description="غير مصرح")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/search",
 *     summary="بحث الكتب مع الفلترة",
 *     description="البحث عن الكتب مع إمكانية الفلترة حسب اسم الفئة",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="title",
 *         in="query",
 *         description="عنوان الكتاب للبحث",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="category_name",
 *         in="query",
 *         description="اسم الفئة للفلترة (مثال: الإدارة)",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="قائمة الكتب المطابقة للبحث",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Book"))
 *         )
 *     ),
 *     @OA\Response(response=401, description="غير مصرح")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/books/history",
 *     summary="عرض سجل استعارة الكتب",
 *     description="عرض سجل استعارة الكتب للطالب الحالي مع عدد الكتب المستعارة",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="سجل الاستعارة",
 *         @OA\JsonContent(
 *             @OA\Property(property="total_borrowed", type="integer", example=5),
 *             @OA\Property(property="history", type="array", @OA\Items(
 *                 @OA\Property(property="book_title", type="string"),
 *                 @OA\Property(property="borrow_date", type="string", format="date-time"),
 *                 @OA\Property(property="return_date", type="string", format="date-time", nullable=true),
 *                 @OA\Property(property="status", type="string", example="borrowed")
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=401, description="غير مصرح")
 * )
 */

// API Documentation
Route::get('/', function () {
    return redirect('/api/documentation');
});

Route::get('/api/documentation', [App\Http\Controllers\SwaggerController::class, 'index'])->withoutMiddleware(['auth:sanctum']);
Route::get('/api/documentation.json', [App\Http\Controllers\SwaggerController::class, 'docs'])->withoutMiddleware(['auth:sanctum']);

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'getCurrentStudent'])->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    /**
     * @OA\Get(
     *     path="/api/search",
     *     summary="بحث الكتب مع الفلترة",
     *     description="البحث عن الكتب مع إمكانية الفلترة حسب اسم الفئة",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="عنوان الكتاب للبحث",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category_name",
     *         in="query",
     *         description="اسم الفئة للفلترة (مثال: الإدارة)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="قائمة الكتب المطابقة للبحث",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Book"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح")
     * )
     */
    Route::get('/search', [SearchController::class, 'search']);

    // Library routes
    Route::post('/qr-login', [QrController::class, 'checkIn']);
    Route::post('/qr-logout', [QrController::class, 'checkOut']);

    // Student routes
    Route::get('/students/{id}', [studentsController::class, 'show']);
    Route::put('/students/{id}', [studentsController::class, 'update']);

    // Books routes
    Route::get('/books/all', [BookController::class, 'listAllBooks']);
    Route::post('/books/borrow', [BookController::class, 'borrowBook']);
    Route::post('/books/return', [BookController::class, 'returnBook']);
    Route::get('/books', [BookController::class, 'index']);
    Route::post('/books', [BookController::class, 'store']);
    Route::get('/books/history', [BookController::class, 'getBorrowingHistory']);
    Route::get('/books/{id}', [BookController::class, 'show']);
    Route::put('/books/{id}', [BookController::class, 'update']);
    Route::delete('/books/{id}', [BookController::class, 'destroy']);

    // Categories routes
    Route::get('/categories', [SearchController::class, 'listCategories']);
    Route::get('/categories/{name}/books', [SearchController::class, 'getBooksByCategory']);
});