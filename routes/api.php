<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\TicketTypeController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\DiscountController;
use App\Http\Controllers\API\PaymentController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/change-user-role', [AuthController::class, 'changeUserRole'])
        ->middleware(['auth:sanctum']);

    Route::get('/users', [AuthController::class, 'getUsers']);

    Route::get('/events/statistics', [EventController::class, 'statistics']);

    Route::post('/discounts/validate', [DiscountController::class, 'validateDiscount']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('events', EventController::class);
    Route::apiResource('ticket-types', TicketTypeController::class);
    Route::apiResource('orders', OrderController::class);
    Route::post('scan', [OrderController::class, 'scan'])->name('orders.scan');


    Route::apiResource('discounts', DiscountController::class);
    Route::apiResource('payments', PaymentController::class);
});
