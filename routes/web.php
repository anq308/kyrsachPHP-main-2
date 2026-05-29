<?php

use App\Http\Controllers\Api\SpaApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/bootstrap', [SpaApiController::class, 'bootstrap']);
    Route::get('/csrf-token', [SpaApiController::class, 'csrfToken']);
    Route::get('/home', [SpaApiController::class, 'home']);
    Route::get('/catalog', [SpaApiController::class, 'catalog']);
    Route::get('/pickup-points', [SpaApiController::class, 'pickupPoints']);
    Route::get('/motorcycles/{id}', [SpaApiController::class, 'motorcycle']);

    Route::get('/cart', [SpaApiController::class, 'cartIndex']);
    Route::post('/cart/{id}', [SpaApiController::class, 'cartAdd']);
    Route::delete('/cart/{id}', [SpaApiController::class, 'cartRemove']);
    Route::post('/checkout', [SpaApiController::class, 'checkout']);

    Route::get('/compare', [SpaApiController::class, 'compareIndex']);
    Route::post('/compare/{id}', [SpaApiController::class, 'toggleCompare']);

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/contact', [SpaApiController::class, 'contact']);
        Route::post('/applications', [SpaApiController::class, 'storeSalesRequest']);
        Route::post('/sales-requests', [SpaApiController::class, 'storeSalesRequest']);
        Route::post('/service-requests', [SpaApiController::class, 'storeServiceRequest']);
    });

    Route::get('/me', [SpaApiController::class, 'me']);
    Route::post('/login', [SpaApiController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [SpaApiController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/logout', [SpaApiController::class, 'logout']);

    Route::middleware('auth')->group(function () {
        Route::get('/favorites', [SpaApiController::class, 'favoritesIndex']);
        Route::post('/favorites/{id}', [SpaApiController::class, 'toggleFavorite']);
        Route::get('/profile', [SpaApiController::class, 'profile']);
        Route::patch('/profile/notifications/read', [SpaApiController::class, 'markNotificationsRead']);
        Route::get('/profile/applications', [SpaApiController::class, 'salesRequestsIndex']);
        Route::get('/profile/service-requests', [SpaApiController::class, 'serviceRequestsIndex']);
        Route::get('/applications', [SpaApiController::class, 'salesRequestsIndex']);
        Route::get('/sales-requests', [SpaApiController::class, 'salesRequestsIndex']);
        Route::get('/service-requests', [SpaApiController::class, 'serviceRequestsIndex']);
    });

    Route::middleware(['auth', 'is_admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [SpaApiController::class, 'adminDashboard']);
        Route::get('/orders', [SpaApiController::class, 'adminOrdersIndex']);
        Route::post('/motorcycles', [SpaApiController::class, 'adminStoreMotorcycle']);
        Route::put('/motorcycles/{id}', [SpaApiController::class, 'adminUpdateMotorcycle']);
        Route::delete('/motorcycles/{id}', [SpaApiController::class, 'adminDeleteMotorcycle']);
        Route::patch('/orders/{id}/status', [SpaApiController::class, 'adminUpdateOrderStatus']);
        Route::get('/applications', [SpaApiController::class, 'adminSalesRequestsIndex']);
        Route::patch('/applications/{id}/status', [SpaApiController::class, 'adminUpdateSalesRequestStatus']);
        Route::delete('/applications/{id}', [SpaApiController::class, 'adminDeleteSalesRequest']);
        Route::get('/sales-requests', [SpaApiController::class, 'adminSalesRequestsIndex']);
        Route::patch('/sales-requests/{id}/status', [SpaApiController::class, 'adminUpdateSalesRequestStatus']);
        Route::delete('/sales-requests/{id}', [SpaApiController::class, 'adminDeleteSalesRequest']);
        Route::get('/service-requests', [SpaApiController::class, 'adminServiceRequestsIndex']);
        Route::patch('/service-requests/{id}/status', [SpaApiController::class, 'adminUpdateServiceRequestStatus']);
        Route::delete('/service-requests/{id}', [SpaApiController::class, 'adminDeleteServiceRequest']);
        Route::get('/users', [SpaApiController::class, 'adminUsersIndex']);
        Route::patch('/users/{id}/role', [SpaApiController::class, 'adminUpdateUserRole']);
    });
});

Route::view('/{any}', 'app')->where('any', '^(?!api).*$');
