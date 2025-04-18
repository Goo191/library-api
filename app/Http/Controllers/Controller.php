<?php

namespace App\Http\Controllers;
/**
 * @OA\Info(
 *     title="نظام المكتبة الجامعية",
 *     version="1.0.0",
 *     description="توثيق API لنظام المكتبة الجامعية",
 *     @OA\Contact(
 *         email="admin@university.com"
 *     )
 * ),
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * ),
 * @OA\Tag(
 *     name="Authentication",
 *     description="نقاط النهاية الخاصة بتسجيل الدخول والخروج"
 * ),
 * @OA\Tag(
 *     name="Library Access",
 *     description="نقاط النهاية الخاصة بالدخول والخروج من المكتبة"
 * )
 */
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
