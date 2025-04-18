<?php

namespace App\Http\Controllers;

use App\Models\books;
use App\Models\borrows;
use App\Models\qr_logs;
use App\Models\categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
class BookController extends Controller
{
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="عنوان الكتاب"),
     *                     @OA\Property(property="quantity", type="integer", example=5),
     *                     @OA\Property(property="category", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     @OA\Property(property="authors", type="array", @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح")
     * )
     */
    public function index(Request $request)
    {
        $query = books::with(['category', 'authors']);
        
        // البحث حسب اسم الفئة
        if ($request->has('category')) {
            $categoryName = $request->category;
            $category = categories::where('name', 'like', '%' . $categoryName . '%')->first();
            
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }
        
        // البحث حسب اسم الكتاب
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $books = $query->paginate(10);
        return response()->json($books);
    }

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
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'quantity' => 'required|integer',
            'publish_year' => 'nullable|integer',
            'category_id' => 'required|exists:categories,id',
            'file' => 'nullable|string'
        ]);

        $book = books::create([
            'title' => $request->title,
            'quantity' => $request->quantity,
            'publish_year' => $request->publish_year,
            'category_id' => $request->category_id,
            'file' => $request->file,
            'qr_code' => uniqid('book_', true)
        ]);

        return response()->json([
            'message' => 'تم إضافة الكتاب بنجاح',
            'book' => $book
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/books/{id}",
     *     summary="عرض تفاصيل كتاب",
     *     description="عرض تفاصيل كتاب معين",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="معرف الكتاب",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="تفاصيل الكتاب",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="quantity", type="integer"),
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string")
     *             ),
     *             @OA\Property(property="authors", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح"),
     *     @OA\Response(response=404, description="الكتاب غير موجود")
     * )
     */
    public function show($id)
    {
        $book = books::with(['category', 'authors'])
            ->where('id', $id)
            ->first();

        if (!$book) {
            return response()->json([
                'message' => 'لم يتم العثور على الكتاب'
            ], 404);
        }

        return response()->json($book);
    }

    /**
     * @OA\Put(
     *     path="/api/books/{id}",
     *     summary="تحديث كتاب",
     *     description="تحديث معلومات كتاب موجود",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="معرف الكتاب",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
     *         response=200,
     *         description="تم تحديث الكتاب بنجاح",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="تم تحديث الكتاب بنجاح"),
     *             @OA\Property(property="book", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="quantity", type="integer"),
     *                 @OA\Property(property="publish_year", type="integer"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="qr_code", type="string", nullable=true),
     *                 @OA\Property(property="file", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح"),
     *     @OA\Response(response=404, description="الكتاب غير موجود"),
     *     @OA\Response(response=422, description="بيانات غير صالحة")
     * )
     */
    public function update(Request $request, $id)
    {
        $book = books::find($id);
        
        if (!$book) {
            return response()->json([
                'message' => 'لم يتم العثور على الكتاب'
            ], 404);
        }

        $request->validate([
            'title' => 'required|string',
            'quantity' => 'required|integer',
            'publish_year' => 'nullable|integer',
            'category_id' => 'required|exists:categories,id',
            'file' => 'nullable|string'
        ]);

        $book->update([
            'title' => $request->title,
            'quantity' => $request->quantity,
            'publish_year' => $request->publish_year,
            'category_id' => $request->category_id,
            'file' => $request->file
        ]);

        return response()->json([
            'message' => 'تم تحديث الكتاب بنجاح',
            'book' => $book
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/books/{id}",
     *     summary="حذف كتاب",
     *     description="حذف كتاب من المكتبة",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="معرف الكتاب",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="تم حذف الكتاب بنجاح",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="تم حذف الكتاب بنجاح")
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح"),
     *     @OA\Response(response=404, description="الكتاب غير موجود")
     * )
     */
    public function destroy($id)
    {
        $book = books::find($id);
        
        if (!$book) {
            return response()->json([
                'message' => 'لم يتم العثور على الكتاب'
            ], 404);
        }

        // التحقق من عدم وجود استعارات نشطة للكتاب
        $activeLoans = borrows::where('book_id', $id)
            ->whereNull('return_date')
            ->count();
            
        if ($activeLoans > 0) {
            return response()->json([
                'message' => 'لا يمكن حذف الكتاب لوجود نسخ مستعارة'
            ], 400);
        }

        // حذف الكتاب
        $book->delete();

        return response()->json([
            'message' => 'تم حذف الكتاب بنجاح'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/books/borrow",
     *     summary="استعارة كتاب",
     *     description="استعارة كتاب عن طريق مسح رمز QR",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qr_code"},
     *             @OA\Property(property="qr_code", type="string", example="book_123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="تم استعارة الكتاب بنجاح",
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
    public function borrowBook(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        \Log::info('Borrow request received', [
            'qr_code' => $request->qr_code,
            'user_id' => Auth::id()
        ]);

        // التحقق من تواجد الطالب في المكتبة
        $lastLog = qr_logs::where('student_id', Auth::id())
            ->whereNull('check_out')
            ->latest('check_in')
            ->first();

        if (!$lastLog) {
            \Log::warning('Student not in library', ['user_id' => Auth::id()]);
            return response()->json([
                'message' => 'يجب تسجيل الدخول للمكتبة أولاً'
            ], 400);
        }

        // استخراج رقم الكتاب من اسم ملف QR
        $qrCode = $request->qr_code;
        if (preg_match('/book_(\d+)/', $qrCode, $matches)) {
            $bookId = $matches[1];
            $book = books::find($bookId);
        } else {
            \Log::warning('Invalid QR code format', ['qr_code' => $qrCode]);
            return response()->json([
                'message' => 'رمز QR غير صالح'
            ], 400);
        }
        
        if (!$book) {
            \Log::warning('Book not found', [
                'book_id' => $bookId,
                'qr_code' => $qrCode
            ]);
            return response()->json([
                'message' => 'لم يتم العثور على الكتاب'
            ], 404);
        }

        // التحقق من أن الكتاب متاح للاستعارة
        if ($book->quantity <= 0) {
            return response()->json([
                'message' => 'الكتاب غير متوفر حالياً'
            ], 400);
        }

        // التحقق من أن الطالب لم يستعر هذا الكتاب مسبقاً
        $existingBorrow = borrows::where('book_id', $book->id)
            ->where('student_id', Auth::id())
            ->whereNull('return_date')
            ->first();

        if ($existingBorrow) {
            return response()->json([
                'message' => 'لديك نسخة مستعارة من هذا الكتاب بالفعل'
            ], 400);
        }

        // إنشاء عملية استعارة جديدة
        $borrow = new borrows();
        $borrow->book_id = $book->id;
        $borrow->student_id = Auth::id();
        $borrow->borrow_date = now();
        $borrow->borrow_status = 'borrowed';
        $borrow->save();

        // تحديث كمية الكتاب
        $book->quantity -= 1;
        $book->save();

        return response()->json([
            'message' => 'تم استعارة الكتاب بنجاح',
            'borrow' => [
                'book_title' => $book->title,
                'borrow_date' => $borrow->borrow_date
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/books/return",
     *     summary="إرجاع كتاب",
     *     description="إرجاع كتاب عن طريق مسح رمز QR",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qr_code"},
     *             @OA\Property(property="qr_code", type="string", example="book_123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="تم إرجاع الكتاب بنجاح",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="تم إرجاع الكتاب بنجاح"),
     *             @OA\Property(property="book", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="لا يوجد استعارة نشطة لهذا الكتاب"),
     *     @OA\Response(response=401, description="غير مصرح"),
     *     @OA\Response(response=404, description="الكتاب غير موجود")
     * )
     */
    public function returnBook(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        // التحقق من تواجد الطالب في المكتبة
        $lastLog = qr_logs::where('student_id', Auth::id())
            ->whereNull('check_out')
            ->latest('check_in')
            ->first();

        if (!$lastLog) {
            return response()->json([
                'message' => 'يجب تسجيل الدخول للمكتبة أولاً'
            ], 400);
        }

        // البحث عن الكتاب باستخدام QR code
        $qrCode = $request->qr_code;
        // إزالة الامتداد من اسم الملف إذا كان موجوداً
        $qrCode = pathinfo($qrCode, PATHINFO_FILENAME);
        
        $book = books::where('qr_code', 'like', '%' . $qrCode . '%')->first();
        
        if (!$book) {
            return response()->json([
                'message' => 'لم يتم العثور على الكتاب'
            ], 404);
        }

        // البحث عن عملية الاستعارة
        $borrow = borrows::where('book_id', $book->id)
            ->where('student_id', Auth::id())
            ->whereNull('return_date')
            ->first();

        if (!$borrow) {
            return response()->json([
                'message' => 'لم يتم العثور على عملية استعارة لهذا الكتاب'
            ], 404);
        }

        // تحديث عملية الاستعارة
        $borrow->return_date = now();
        $borrow->borrow_status = 'returned';
        $borrow->save();

        // تحديث كمية الكتاب
        $book->quantity += 1;
        $book->save();

        return response()->json([
            'message' => 'تم إرجاع الكتاب بنجاح',
            'book_title' => $book->title
        ]);
    }

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
     *             @OA\Property(property="books", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="qr_code", type="string"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(property="category", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     @OA\Property(property="authors", type="array",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح")
     * )
     */
    public function listAllBooks()
    {
        $books = books::with(['category', 'authors'])->get();
        
        if ($books->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد كتب متاحة حالياً'
            ], 404);
        }

        return response()->json([
            'books' => $books
        ]);
    }

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
    public function getBorrowingHistory()
    {
        try {
            $studentId = Auth::id();
            
            // Get all borrows for the current student
            $borrows = borrows::where('student_id', $studentId)
                ->with('book')
                ->orderBy('borrow_date', 'desc')
                ->get();

            // Format the history data
            $history = $borrows->map(function ($borrow) {
                return [
                    'book_title' => $borrow->book->title,
                    'borrow_date' => $borrow->borrow_date,
                    'return_date' => $borrow->return_date,
                    'status' => $borrow->borrow_status
                ];
            });

            // Count total borrowed books
            $totalBorrowed = $borrows->count();

            return response()->json([
                'total_borrowed' => $totalBorrowed,
                'history' => $history,
                'message' => $totalBorrowed > 0 ? 'تم العثور على سجل الاستعارة' : 'لا يوجد سجل استعارة'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب سجل الاستعارة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 