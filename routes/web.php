<?php

use App\Http\Controllers\AwardsController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\HackathonController;
use App\Http\Controllers\HackathonStaffController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SupportsController;
use App\Http\Controllers\TabController;
use App\Http\Controllers\TeamController;
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

Route::get('/lang/switch/{locale}', [LanguageController::class, 'switchLang'])->name('lang.switch');
Route::get('/lang/{locale}.json', [LanguageController::class, 'json'])->name('lang.json');

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'index'])->name('login');
    Route::get('/auth/redirect', [SessionController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/callback', [SessionController::class, 'callback'])->name('auth.callback');
});

Route::get('/', [HackathonController::class, 'index'])->name('home');

Route::prefix('hackathons')->name('hackathons.')->group(function () {
    //
    Route::prefix('/{hackathon}')->group(function () {
        Route::get('/', [HackathonController::class, 'show'])->name('show');
        Route::prefix('/tabs')->name('tabs.')->group(function () {
            //
        });
        Route::prefix('/projects/{project}')->name('projects.')->group(function () {
            Route::get('/', [ProjectsController::class, 'show'])->name('show');
        });
    });
});

Route::get('/profile/{user}', [UserController::class, 'show'])->name('profile.show');
Route::get('/profile', [UserController::class, 'showMe'])->name('profile.my');

Route::get('/notification', [NotificationController::class, 'index'])->name('notification.index');
Route::patch('/notification/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notification.mark-as-read');

Route::middleware('auth')->group(function () {
    Route::get('/logout', [SessionController::class, 'logout'])->name('logout');
    Route::get('/my-hackathons', [HackathonController::class, 'myHackathons'])->name('my-hackathons');
    Route::prefix('hackathons')->name('hackathons.')->group(function () {
        Route::post('/', [HackathonController::class, 'store'])->name('store');
        Route::prefix('/{hackathon}')->group(function () {
            Route::patch('/', [HackathonController::class, 'update'])->name('update');
            Route::post('/join', [HackathonController::class, 'joinHackathon'])->name('join');
            Route::post('/leave', [HackathonController::class, 'leaveHackathon'])->name('leave');
            Route::prefix('/tabs')->name('tabs.')->group(function () {
                Route::patch('/', [TabController::class, 'update'])->name('update');
            });
            Route::prefix('/nominations')->name('nominations.')->group(function () {
                Route::post('/', [NominationController::class, 'store'])->name('store');
                Route::patch('/{nomination}', [NominationController::class, 'update'])->name('update');
                Route::delete('/{nomination}', [NominationController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('/criterionGroup')->name('criteria.')->group(function () {
                Route::post('/', [CriteriaController::class, 'store'])->name('store');
                Route::patch('/{criterionGroup}', [CriteriaController::class, 'update'])->name('update');
                Route::delete('/{criterionGroup}', [CriteriaController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('/awards')->name('awards.')->group(function () {
                Route::post('/', [AwardsController::class, 'store'])->name('store');
                Route::patch('/{award}', [AwardsController::class, 'update'])->name('update');
                Route::delete('/{award}', [AwardsController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('/teams')->name('teams.')->group(function () {
                Route::get('/', [TeamController::class, 'index'])->name('index');
                Route::prefix('/{team}')->group(function () {
                    Route::patch('/', [TeamController::class, 'update'])->name('update');
                    Route::delete('/kick', [TeamController::class, 'kick'])->name('kick');
                    Route::post('/invite', [TeamController::class, 'createInvite'])->name('create-invite');
                    Route::get('/invite/{token}', [TeamController::class, 'acceptInvite'])->name('accept-invite');
                    Route::post('/inviteById', [TeamController::class, 'inviteUserById'])->name('invite-by-id');
                    Route::prefix('/projects')->name('projects.')->group(function () {
                        Route::post('/', [ProjectsController::class, 'store'])->name('store');
                        Route::prefix('/{project}')->group(function () {
                            Route::post('/publish', [ProjectsController::class, 'publish'])->name('publish');
                        });
                    });
                });
            });
            Route::prefix('/projects')->name('projects.')->group(function () {
                Route::get('/', [ProjectsController::class, 'index'])->name('index');
                Route::prefix('/{project}}')->group(function () {
                    Route::patch('/', [ProjectsController::class, 'update'])->name('update');
                    Route::delete('/', [ProjectsController::class, 'destroy'])->name('destroy');
                });
            });

            Route::prefix('/support')->name('support.')->group(function () {
                Route::get('/', [SupportsController::class, 'index'])->name('index');
            });

            Route::prefix('/staff')->name('staff.')->group(function () {
                Route::delete('/kick', [HackathonStaffController::class, 'kick'])->name('kick');
                Route::post('/invite', [HackathonStaffController::class, 'createInvite'])->name('create-invite');
                Route::get('/invite/{token}', [HackathonStaffController::class, 'acceptInvite'])->name('accept-invite');
                Route::post('/inviteById', [HackathonStaffController::class, 'inviteUserById'])->name('invite-by-id');
            });
        });
    });
});

Route::prefix('hackathons/{hackathon}/')->name('hackathons.')->group(function () {
    Route::get('/media', [MediaController::class, 'showHackathonMedia'])->name('image');
    Route::get('/media-mobile', [MediaController::class, 'showHackathonMediaMobile'])->name('image-mobile');
    Route::get('/files/{media}', [MediaController::class, 'showHackathonFile'])->name('files.download');
    Route::prefix('/tabs/{tab}')->name('tabs.')->group(function () {
        Route::get('/partner-images', [MediaController::class, 'showHackathonPartners'])->name('partner-images');
    });
    Route::prefix('/projects/{project}')->name('projects.')->group(function () {
        Route::get('/preview', [MediaController::class, 'showProjectPreview'])->name('image');
        Route::get('/presentation', [MediaController::class, 'showProjectPresentation'])->name('presentation');
        Route::get('/gallery', [MediaController::class, 'showProjectGallery'])->name('gallery');
    });
});

Route::get('/awards/{award}/media', [MediaController::class, 'showAwardMedia'])->name('awards.image');
