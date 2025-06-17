<?php

use App\Http\Controllers\HackathonController;
use App\Http\Controllers\TabController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HackathonController::class, 'index'])->name('index');

Route::prefix('hackathons')->name('hackathons.')->group(function () {
    Route::post('/', [HackathonController::class, 'store'])->name('store');
    Route::prefix('/{hackathon}')->group(function () {
        Route::get('/', [HackathonController::class, 'show'])->name('show');
        Route::prefix('/tabs')->name('tabs.')->group(function () {
            Route::put('/', [TabController::class, 'update'])->name('update');
        });
    });
});

Route::get('/users/{user}', [UserController::class, 'show'])->middleware(['auth'])->name('user.show');

Route::middleware('auth')->group(function () {
    Route::get('/my-hackathons', [HackathonController::class, 'myHackathons'])->name('my-hackathons');
//    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
//    Route::prefix('hackathons')->name('hackathons.')->group(function () {
//        Route::post('/', [HackathonController::class, 'store'])->name('store');
//        Route::prefix('/{hackathon}')->group(function () {
//                Route::prefix('/tabs')->name('tabs.')->group(function () {
//                    Route::put('/', [TabController::class, 'update'])->name('update');
//            });
//        });
//    });
});

Route::prefix('storage/hackathons/{hackathon}/')->name('hackathons.')->group(function () {
    Route::get('/media', [HackathonController::class, 'showMedia'])->name('image');
    Route::get('/media-mobile', [HackathonController::class, 'showMediaMobile'])->name('image-mobile');
});


require __DIR__.'/auth.php';
