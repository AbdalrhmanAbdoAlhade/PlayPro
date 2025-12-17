<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\FieldImageController;
use App\Http\Controllers\FieldBookingController;
use App\Http\Controllers\FieldPeriodController;
use App\Http\Controllers\AppInfoController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\NewsEventController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ChairmanMessageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\TransferRequestController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\RatingController;


// Route لإرسال الإيميل عبر الـ API
Route::post('/send-mail', [MailController::class, 'sendMail']);



// ============================
// Public Routes (No Auth)
// ============================

// User registration & login
Route::post('/register', [UsersController::class, 'register']);
Route::post('/login', [UsersController::class, 'login']);
Route::post('/registerOwner', [UsersController::class, 'registerOwner']);

Route::get('/pending-registrations', [UsersController::class, 'getPendingRegistrations'])
    ->middleware('auth:sanctum');
    Route::post('/users/{userId}/role', [UsersController::class, 'updateRole'])
    ->middleware('auth:sanctum'); // أو حسب الحماية المطلوبة
    Route::get('fields/', [FieldController::class, 'index']); 
 Route::get('fields/{id}', [FieldController::class, 'show']);  
 
// ============================
// Protected Routes (Auth: Sanctum)
// ============================
Route::middleware('auth:sanctum')->group(function () {

    // ----------------------------
    // Users Management
    // ----------------------------
    Route::prefix('users')->group(function () {

        // Current authenticated user
        Route::get('profile', [UsersController::class, 'profile']);
        Route::post('profile', [UsersController::class, 'updateProfile']);
        Route::post('reset-password', [UsersController::class, 'resetPassword']);
        Route::post('logout', [UsersController::class, 'logout']);

        // Admin-only routes
        Route::middleware('auth:sanctum')->group(function (){
            Route::get('/', [UsersController::class, 'index']);
            Route::delete('{id}', [UsersController::class, 'destroy']);
        });
    });

    // ----------------------------
    // Fields Management
    // ----------------------------
    Route::get('/my-fields', [FieldController::class, 'myFields']);
    Route::prefix('fields')->group(function () {

          // List all fields
        Route::post('/', [FieldController::class, 'store']);      // Create new field
         // Show single field
        Route::put('{id}', [FieldController::class, 'update']);  // Update field
        Route::delete('{id}', [FieldController::class, 'destroy']); // Delete field

        // ----------------------------
        // Field Images
        // ----------------------------
        Route::get('{field}/images', [FieldImageController::class, 'index']);
        Route::post('{field}/images', [FieldImageController::class, 'store']);
        Route::delete('images/{image}', [FieldImageController::class, 'destroy']);
        Route::post('images/{image}/make-icon', [FieldImageController::class, 'makeIcon']);

        // ----------------------------
        // Field Periods
        // ----------------------------
        Route::get('{field}/periods', [FieldPeriodController::class, 'index']);
        Route::post('{field}/periods', [FieldPeriodController::class, 'store']);
        Route::get('{field}/periods/{period}', [FieldPeriodController::class, 'show']);
        Route::put('{field}/periods/{period}', [FieldPeriodController::class, 'update']);
        Route::delete('{field}/periods/{period}', [FieldPeriodController::class, 'destroy']);
    });

    // ----------------------------
    // Field Bookings
    // ----------------------------
Route::middleware('auth:sanctum')->group(function () {

    // عرض الحجوزات (Admin / Owner)
    Route::get('bookings', [FieldBookingController::class, 'index']);
    // عرض حجوزات المستخدم الحالي
    Route::get('my-bookings', [FieldBookingController::class, 'myBookings']);
    // إنشاء حجز جديد
    Route::post('bookings', [FieldBookingController::class, 'store']);
    // حذف حجز
    Route::delete('bookings/{id}', [FieldBookingController::class, 'destroy']);
    // التحقق من QR Code
    Route::post('bookings/verify-qr', [FieldBookingController::class, 'verifyQr']);
});


   // ----------------------------
    // App info
    // ----------------------------
Route::get('/app-info', [AppInfoController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
Route::post('/app-info', [AppInfoController::class, 'store']);
});



   // ----------------------------
    // ContactMessageController
    // ----------------------------
Route::post('/contact-us', [ContactMessageController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/contact-messages', [ContactMessageController::class, 'index']);
    Route::get('/contact-messages/{id}', [ContactMessageController::class, 'show']);
});


  // ----------------------------
    // PartnerController
    // ----------------------------
    Route::get('partners', [PartnerController::class, 'index']);       
Route::get('partners/{partner}', [PartnerController::class, 'show']); 
Route::middleware('auth:sanctum')->group(function () {

Route::post('partners', [PartnerController::class, 'store']);       
Route::post('partners/{partner}', [PartnerController::class, 'update']); 
Route::delete('partners/{partner}', [PartnerController::class, 'destroy']); 
});


 // ----------------------------
    // NewsEventController
    // ----------------------------
Route::get('news-events', [NewsEventController::class, 'index']);
Route::get('news-events/{newsEvent}', [NewsEventController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('news-events', [NewsEventController::class, 'store']);
    Route::post('news-events/{newsEvent}', [NewsEventController::class, 'update']);
    Route::delete('news-events/{newsEvent}', [NewsEventController::class, 'destroy']);
});



 // ----------------------------
    // BlogController
    // ----------------------------
Route::get('blogs', [BlogController::class, 'index']);
Route::get('blogs/{blog}', [BlogController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('blogs', [BlogController::class, 'store']);
    Route::post('blogs/{blog}', [BlogController::class, 'update']);
    Route::delete('blogs/{blog}', [BlogController::class, 'destroy']);
});



 // ----------------------------
    // ChairmanMessageController
    // ----------------------------
    

Route::get('chairman-messages', [ChairmanMessageController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('chairman-messages', [ChairmanMessageController::class, 'store']);
});

// ----------------------------
    // BannerController
    // ----------------------------
Route::get('banners', [BannerController::class, 'index']);
Route::get('banners/{banner}', [BannerController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('banners', [BannerController::class, 'store']);
    Route::post('banners/{banner}', [BannerController::class, 'update']);
    Route::delete('banners/{banner}', [BannerController::class, 'destroy']);
});


// ----------------------------
    // ProductController
    // ----------------------------
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::post('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
});


// ----------------------------
    // OrderController
    // ----------------------------

Route::middleware('auth:sanctum')->group(function () {
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::delete('orders/{order}', [OrderController::class, 'destroy']);
});


// ----------------------------
    // TransferRequest
    // ----------------------------
    Route::post('transfer-requests/{transferRequest}/reject', [TransferRequestController::class, 'reject'])
    ->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('transfer-requests')->controller(TransferRequestController::class)->group(function () {
        Route::get('/', 'index');        
        Route::post('/', 'store');       
        Route::delete('/{transferRequest}', 'destroy'); 
        Route::post('/{transferRequest}/approve', 'approve'); 
    });
});



// ----------------------------
    // CoachController
    // ----------------------------


    Route::get('/coaches', [CoachController::class, 'index']);
    Route::get('/coaches/{id}', [CoachController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/coaches', [CoachController::class, 'store']);
    Route::post('/coaches/{id}', [CoachController::class, 'update']);
    Route::delete('/coaches/{id}', [CoachController::class, 'destroy']);

});

// ----------------------------
    // RatingController
    // ----------------------------

Route::middleware('auth:sanctum')->post('/rate', [RatingController::class, 'store']);

