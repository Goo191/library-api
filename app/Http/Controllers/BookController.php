<?php

namespace App\Http\Controllers;

use App\Models\books;
use App\Models\borrows;
use App\Models\qr_logs;
use App\Models\categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


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
        
        if ($request->has('category')) {
            $categoryName = $request->category;
            $category = categories::where('name', 'like', '%' . $categoryName . '%')->first();
            
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }
        
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $books */
        $books = $query->paginate(10);
        
        // معالجة مسارات الصور و QR code لكل كتاب
        $books->getCollection()->transform(function ($book) {
            // استخدام قيمة الصورة مباشرة من الكاتيجوري
            if ($book->category && $book->category->image) {
                $book->category_image = $book->category->image;
            }

            // معالجة مسار QR code
            if ($book->qr_code) {
                // استخراج اسم الملف فقط
                $fileName = basename($book->qr_code);
                // إنشاء المسار الصحيح مع asset
                $book->qr_code = asset('storage/qrcodes/' . $fileName);
            }

            return $book;
        });

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
     *     path="/api/books/{title}",
     *     summary="عرض تفاصيل كتاب",
     *     description="عرض تفاصيل كتاب معين باستخدام عنوان الكتاب",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="title",
     *         in="path",
     *         description="عنوان الكتاب",
     *         required=true,
     *         @OA\Schema(type="string")
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
    public function show($title)
    {
        return $this->showByTitle($title);

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

        $qrCode = $request->qr_code;
        $currentBookTitle = $request->current_book_title;

      

        // استخراج معرف الكتاب من رمز QR
        $fileName = basename($qrCode);
        if (preg_match('/book_(\d+)(?:\.(?:png|jpg|jpeg))?$/', $fileName, $matches)) {
            $bookId = $matches[1];
            $book = books::find($bookId);
        } else {
            Log::warning('Invalid QR code format', ['qr_code' => $qrCode]);
            return response()->json([
                'message' => 'رمز QR غير صالح'
            ], 400);
        }
        
        if (!$book) {
            Log::warning('Book not found', [
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

        $qrCode = $request->qr_code;
        $currentBookTitle = $request->current_book_title;

       
        // استخراج معرف الكتاب من رمز QR
        $fileName = basename($qrCode);
        if (preg_match('/book_(\d+)(?:\.(?:png|jpg|jpeg))?$/', $fileName, $matches)) {
            $bookId = $matches[1];
            $book = books::find($bookId);
        } else {
            Log::warning('Invalid QR code format', ['qr_code' => $qrCode]);
            return response()->json([
                'message' => 'رمز QR غير صالح'
            ], 400);
        }
        
        if (!$book) {
            Log::warning('Book not found', [
                'book_id' => $bookId,
                'qr_code' => $qrCode
            ]);
            return response()->json([
                'message' => 'لم يتم العثور على الكتاب'
            ], 404);
        }

        

        $borrow = borrows::where('book_id', $book->id)
            ->where('student_id', Auth::id())
            ->whereNull('return_date')
            ->first();

        if (!$borrow) {
            Log::warning('No active borrow found', [
                'book_id' => $book->id,
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'message' => 'لا يوجد استعارة نشطة لهذا الكتاب'
            ], 400);
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
            'book' => [
                'title' => $book->title,
                'return_date' => $borrow->return_date
            ]
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

        // معالجة مسارات الصور و QR code لكل كتاب
        $books->transform(function ($book) {
            // استخدام قيمة الصورة مباشرة من الكاتيجوري
            if ($book->category && $book->category->image) {
                $book->category_image = $book->category->image;
            }

            // معالجة مسار QR code
            if ($book->qr_code) {
                // استخراج اسم الملف فقط
                $fileName = basename($book->qr_code);
                // إنشاء المسار الصحيح مع asset
                $book->qr_code = asset('storage/qrcodes/' . $fileName);
            }

            return $book;
        });

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

    /**
     * @OA\Get(
     *     path="/api/books/regular",
     *     summary="عرض الكتب العادية",
     *     description="عرض قائمة الكتب العادية (غير مشاريع التخرج)",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="قائمة الكتب العادية",
     *         @OA\JsonContent(
     *             @OA\Property(property="books", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(property="category", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح")
     * )
     */
    public function getRegularBooks()
    {
        try {
            $books = books::with(['category', 'authors'])
                ->whereHas('category', function($query) {
                    $query->whereNotIn('name', ['علوم حاسب', 'نظم']);
                })
                ->get();

            if ($books->isEmpty()) {
                return response()->json([
                    'message' => 'لا توجد كتب عادية متاحة حالياً'
                ], 200);
            }

            // معالجة مسارات الصور و QR code لكل كتاب
            $books->transform(function ($book) {
                // استخدام قيمة الصورة مباشرة من الكاتيجوري
                if ($book->category && $book->category->image) {
                    $book->category_image = $book->category->image;
                }

                // معالجة مسار QR code
                if ($book->qr_code) {
                    // استخراج اسم الملف فقط
                    $fileName = basename($book->qr_code);
                    // إنشاء المسار الصحيح مع asset
                    $book->qr_code = asset('storage/qrcodes/' . $fileName);
                }

                return $book;
            });

            return response()->json([
                'books' => $books
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الكتب العادية',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/books/graduation",
     *     summary="عرض كتب مشاريع التخرج",
     *     description="عرض قائمة كتب مشاريع التخرج (فئات علوم حاسب ونظم)",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="قائمة كتب مشاريع التخرج",
     *         @OA\JsonContent(
     *             @OA\Property(property="books", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(property="category", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح")
     * )
     */
    public function getGraduationBooks()
    {
        try {
            $books = books::with(['category', 'authors'])
                ->whereHas('category', function($query) {
                    $query->whereIn('name', ['علوم حاسب', 'نظم']);
                })
                ->get();

            if ($books->isEmpty()) {
                return response()->json([
                    'message' => 'لا توجد كتب مشاريع تخرج متاحة حالياً'
                ], 200);
            }

            // معالجة مسارات الصور و QR code لكل كتاب
            $books->transform(function ($book) {
                // استخدام قيمة الصورة مباشرة من الكاتيجوري
                if ($book->category && $book->category->image) {
                    $book->category_image = $book->category->image;
                }

                // معالجة مسار QR code
                if ($book->qr_code) {
                    // استخراج اسم الملف فقط
                    $fileName = basename($book->qr_code);
                    // إنشاء المسار الصحيح مع asset
                    $book->qr_code = asset('storage/qrcodes/' . $fileName);
                }

                return $book;
            });

            return response()->json([
                'books' => $books
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب كتب مشاريع التخرج',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/books/study-materials",
     *     summary="عرض الكتب الدراسية للطالب",
     *     description="عرض قائمة الكتب الدراسية المتاحة للطالب الحالي",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="قائمة الكتب الدراسية",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="file", type="string", nullable=true),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="غير مصرح")
     * )
     */
    public function getStudyMaterials()
    {
        $student_id = Auth::id();
        
        $books = books::whereHas('students', function($query) use ($student_id) {
            $query->where('book_student.student_id', $student_id);
        })->with(['category', 'authors'])->get();

        // معالجة مسارات الصور و QR code لكل كتاب
        $books->transform(function ($book) {
            // استخدام قيمة الصورة مباشرة من الكاتيجوري
            if ($book->category && $book->category->image) {
                $book->category_image = $book->category->image;
            }

            // معالجة مسار QR code
            if ($book->qr_code) {
                // استخراج اسم الملف فقط
                $fileName = basename($book->qr_code);
                // إنشاء المسار الصحيح مع asset
                $book->qr_code = asset('storage/qrcodes/' . $fileName);
            }

            // إرجاع اسم الملف الدراسي فقط بدون مسار
            if ($book->file) {
                $book->file = basename($book->file);
            }

            return $book;
        });

        return response()->json([
            'status' => 'success',
            'data' => $books
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/books/{id}/view-pdf",
     *     summary="عرض ملف PDF للكتاب",
     *     description="عرض ملف PDF للكتاب الدراسي (متاح فقط للطلاب المصرح لهم)",
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
     *         description="ملف PDF",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="غير مسموح بالوصول لهذا الكتاب",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="غير مسموح بالوصول لهذا الكتاب")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="ملف PDF غير موجود",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="ملف PDF غير موجود")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطأ في الخادم",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="حدث خطأ أثناء محاولة عرض الملف"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function viewPdf($id)
    {
        try {
            $student_id = Auth::id();
            
            Log::info('Attempting to view PDF', [
                'book_id' => $id,
                'student_id' => $student_id
            ]);

            // التحقق من وجود الطالب
            if (!$student_id) {
                return response()->json([
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            // البحث عن الكتاب باستخدام query builder للتأكد
            $book = DB::table('books')->where('id', $id)->first();
            
            Log::info('Book search result', [
                'book' => $book ? 'found' : 'not found',
                'book_id' => $id
            ]);
            
            if (!$book) {
                return response()->json([
                    'message' => 'الكتاب غير موجود',
                    'details' => [
                        'book_id' => $id,
                        'student_id' => $student_id
                    ]
                ], 404);
            }

            // التحقق من صلاحيات الطالب باستخدام جدول book_student مباشرة
            $hasAccess = DB::table('book_student')
                ->where('book_id', $id)
                ->where('student_id', $student_id)
                ->exists();

            Log::info('Student access check', [
                'has_access' => $hasAccess,
                'book_id' => $id,
                'student_id' => $student_id
            ]);

            if (!$hasAccess) {
                return response()->json([
                    'message' => 'غير مسموح بالوصول لهذا الكتاب',
                    'details' => [
                        'book_id' => $id,
                        'student_id' => $student_id,
                        'book_title' => $book->title
                    ]
                ], 403);
            }

            // التحقق من وجود الملف
            if (!$book->file) {
                return response()->json([
                    'message' => 'ملف PDF غير موجود',
                    'details' => [
                        'book_id' => $id,
                        'book_title' => $book->title
                    ]
                ], 404);
            }

            // تنظيف مسار الملف
            $file_path = str_replace('\\', '/', $book->file);
            $file_path = ltrim($file_path, '/');

            // محاولة العثور على الملف في عدة مسارات محتملة
            $possible_paths = [
                storage_path('app/public/' . $file_path),
                storage_path('app/public/storage/' . $file_path),
                public_path('storage/' . $file_path),
                storage_path('app/' . $file_path)
            ];

            $file_exists = false;
            $correct_path = '';

            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    $file_exists = true;
                    $correct_path = $path;
                    break;
                }
            }
            
            Log::info('File path check', [
                'file_path' => $file_path,
                'possible_paths' => $possible_paths,
                'exists' => $file_exists,
                'correct_path' => $correct_path
            ]);
            
            if (!$file_exists) {
                return response()->json([
                    'message' => 'ملف PDF غير موجود في المسار المحدد',
                    'details' => [
                        'file_path' => $file_path,
                        'checked_paths' => $possible_paths,
                        'book_title' => $book->title
                    ]
                ], 404);
            }

            // عرض الملف مباشرة
            return response()->file(
                $correct_path,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $book->title . '.pdf"',
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'SAMEORIGIN',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                    'Pragma' => 'no-cache'
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error in viewPdf', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'حدث خطأ أثناء محاولة عرض الملف',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * عرض تفاصيل كتاب حسب العنوان
     */
    public function showByTitle($title)
    {
        $book = books::where('title', $title)
            ->with(['category', 'authors'])
            ->first();

        if (!$book) {
            return response()->json(['message' => 'الكتاب غير موجود'], 404);
        }

        // معالجة مسار صورة الفئة
        if($book->category && $book->category->image) {
            // تنظيف المسار من storage/ في البداية إذا وجد
            $cleanPath = str_replace('storage/', '', $book->category->image);
            // إزالة المسافات الزائدة وإصلاح المسار
            $cleanPath = trim($cleanPath);
            $cleanPath = str_replace(' categories/', 'categories/', $cleanPath);
            $cleanPath = str_replace(' categories', 'categories', $cleanPath);
            $cleanPath = str_replace('storage/', '', $cleanPath); // إزالة storage/ مرة أخرى للتأكد
            // إنشاء المسار الصحيح مع asset
            $book->category_image = asset('storage/' . $cleanPath);
        }


        // تنظيف مسار رمز QR
       
        if ($book->qr_code) {
            // تنظيف المسار من storage/ في البداية إذا وجد
            $cleanPath = str_replace('storage/', '', $book->qr_code);
            // إزالة المسافات الزائدة
            $cleanPath = trim($cleanPath);
            // استخراج اسم الملف فقط
            $fileName = basename($cleanPath);
            // إنشاء المسار الصحيح مع asset
            $book->qr_code = asset('storage/qrcodes/' . $fileName);
        }

        return response()->json($book);
    }

    /**
     * عرض الكتب حسب التصنيف
     */
    public function getBooksByCategory($name)
    {
        $category = categories::where('name', $name)->first();
        
        if (!$category) {
            return response()->json([
                'message' => 'لم يتم العثور على التصنيف'
            ], 404);
        }

        $books = books::where('category_id', $category->id)
            ->with(['category', 'authors'])
            ->get();

        // معالجة مسارات الصور و QR code لكل كتاب
        $books->transform(function ($book) {
            // معالجة مسار صورة التصنيف
            if ($book->category && $book->category->image) {
                // تنظيف المسار من storage/ في البداية إذا وجد
                $cleanPath = str_replace('storage/', '', $book->category->image);
                // إزالة المسافات الزائدة وإصلاح المسار
                $cleanPath = trim($cleanPath);
                $cleanPath = str_replace(' categories/', 'categories/', $cleanPath);
                $cleanPath = str_replace(' categories', 'categories', $cleanPath);
                // إنشاء المسار الصحيح مع asset
                $book->category_image = asset('storage/' . $cleanPath);
            }

            // معالجة مسار QR code
            if ($book->qr_code) {
                // تنظيف المسار من storage/ في البداية إذا وجد
                $cleanPath = str_replace('storage/', '', $book->qr_code);
                // إزالة المسافات الزائدة
                $cleanPath = trim($cleanPath);
                // استخراج اسم الملف فقط
                $fileName = basename($cleanPath);
                // إنشاء المسار الصحيح مع asset
                $book->qr_code = asset('storage/qrcodes/' . $fileName);
            }

            return $book;
        });

        return response()->json([
            'books' => $books
        ]);
    }


   /**
 * @OA\Get(
 *     path="/api/books/current/{title}",
 *     summary="التحقق من أن الكتاب الحالي هو المطلوب",
 *     description="تستخدم هذه النقطة للتحقق من أن عنوان الكتاب الحالي هو نفسه قبل عملية الاستعارة أو الإرجاع.",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}}, 
 *     @OA\Parameter(
 *         name="title",
 *         in="path",
 *         description="عنوان الكتاب للتحقق منه",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="الكتاب متاح",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="الكتاب متاح"),
 *             @OA\Property(property="book", type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="title", type="string"),
 *                 @OA\Property(property="description", type="string", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="الكتاب غير موجود",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="الكتاب غير موجود")
 *         )
 *     )
 * )
 */
    public function verifyCurrentBook($title)
    {
        $book = books::where('title', $title)->first();

        if (!$book) {
            return response()->json([
                'message' => 'الكتاب غير موجود'
            ], 404);
        }

        return response()->json([
            'message' => 'الكتاب متاح',
            'book' => [
                'id' => $book->id,
                'title' => $book->title
            ]
        ]);
    }
    /**
 * @OA\Post(
 *     path="/api/books/verify-scan",
 *     summary="التحقق من تطابق QR Code مع الكتاب الحالي",
 *     description="تستخدم هذه النقطة للتحقق مما إذا كان QR Code الممسوح يخص الكتاب الحالي المعروض قبل تنفيذ أي عملية.",
 *     tags={"Books"},
 *     security={{"bearerAuth":{}}}, 
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"current_book_id", "scanned_qr_code"},
 *             @OA\Property(
 *                 property="current_book_id",
 *                 type="integer",
 *                 example=2001,
 *                 description="معرف الكتاب الحالي"
 *             ),
 *             @OA\Property(
 *                 property="scanned_qr_code",
 *                 type="string",
 *                 example="book_2001",
 *                 description="رمز QR الذي تم مسحه"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="تطابق QR Code بنجاح",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="QR Code matches the book."),
 *             @OA\Property(
 *                 property="book",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=2001),
 *                 @OA\Property(property="title", type="string", example="تجارب ومشاريع عملية على استخدام الدوائر الرقمية")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="فشل التحقق - الكود لا يطابق",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="QR Code does not match the book.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="الكتاب غير موجود",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Book not found.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="غير مصرح - تحتاج إلى تسجيل الدخول"
 *     )
 * )
 */

 public function verifyScan(Request $request)
 {
     $request->validate([
         'current_book_id' => 'required|integer',
         'scanned_qr_code' => 'required|string',
     ]);
 
     $book = books::find($request->current_book_id);
 
     if (!$book) {
         return response()->json([
             'success' => false,
             'message' => 'الكتاب غير موجود.'
         ], 404);
     }
 
     // تجهيز الـ qr_code قبل المقارنة
     $expectedQrCode = pathinfo($book->qr_code, PATHINFO_FILENAME); // هيشيل المسار والامتداد ويخلي بس اسم الملف
 
     if ($expectedQrCode === $request->scanned_qr_code) {
         return response()->json([
             'success' => true,
             'message' => 'QR Code مطابق للكتاب.'
         ]);
     } else {
         return response()->json([
             'success' => false,
             'message' => 'QR Code لا يطابق هذا الكتاب.'
         ], 400);
     }
 }
 
} 