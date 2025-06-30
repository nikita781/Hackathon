<?php

use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\HackathonController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NominationController;
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

Route::get('/lang/{locale}', LanguageController::class)->name('lang.switch');

Route::get('/', [HackathonController::class, 'index'])->name('home');

Route::prefix('hackathons')->name('hackathons.')->group(function () {
    //
    Route::prefix('/{hackathon}')->group(function () {
        Route::get('/', [HackathonController::class, 'show'])->name('show');
        //
        Route::prefix('/tabs')->name('tabs.')->group(function () {
            //
        });
    });
});

Route::get('/users/{user}', [UserController::class, 'show'])->middleware(['auth'])->name('user.show');

Route::middleware('auth')->group(function () {
    Route::get('/my-hackathons', [HackathonController::class, 'myHackathons'])->name('my-hackathons');
    Route::prefix('hackathons')->name('hackathons.')->group(function () {
        Route::post('/', [HackathonController::class, 'store'])->name('store');
        Route::prefix('/{hackathon}')->group(function () {
            Route::post('/join', [HackathonController::class, 'join'])->name('join');
            Route::prefix('/tabs')->name('tabs.')->group(function () {
                Route::patch('/', [TabController::class, 'update'])->name('update');
            });
            Route::prefix('/nominations')->name('nominations.')->group(function () {
                Route::post('/', [NominationController::class, 'store'])->name('store');
                Route::patch('/{nomination}', [NominationController::class, 'update'])->name('update');
                Route::delete('/{nomination}', [NominationController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('/criteria')->name('criteria.')->group(function () {
                Route::post('/', [CriteriaController::class, 'store'])->name('store');
                Route::patch('/{criteria}', [CriteriaController::class, 'update'])->name('update');
                Route::delete('/{criteria}', [CriteriaController::class, 'destroy'])->name('destroy');
            });
        });
    });
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

Route::prefix('hackathons/{hackathon}/')->name('hackathons.')->group(function () {
    Route::get('/media', [MediaController::class, 'showHackathonMedia'])->name('image');
    Route::get('/media-mobile', [MediaController::class, 'showHackathonMediaMobile'])->name('image-mobile');
    Route::prefix('/tabs/{tab}')->name('tabs.')->group(function () {
        Route::get('/partner-images', [MediaController::class, 'showHackathonPartners'])->name('partner-images');
    });
});

Route::get('/hackathons/{hackathon}/files/{media}', [MediaController::class, 'showHackathonFile'])->name('hackathons.files.download');

require __DIR__.'/auth.php';
