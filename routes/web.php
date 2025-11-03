<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AwardsController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\HackathonController;
use App\Http\Controllers\HackathonStaffController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SupportsController;
use App\Http\Controllers\TabController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// LOCALE ROUTES
Route::get('/lang/switch/{locale}', [LanguageController::class, 'switchLang'])->name('lang.switch');
Route::get('/lang/{locale}.json', [LanguageController::class, 'json'])->name('lang.json');

// REGISTER ROUTES
Route::middleware('guest')->group(function () {
    Route::post('/login', [SessionController::class, 'login'])->name('login')->middleware('throttle:10,1');
    Route::get('/login', [SessionController::class, 'loginGet'])->name('login-get');
});

// HOME
Route::get('/', [HackathonController::class, 'index'])->name('home');

// GUEST ROUTES
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

// NOTIFICATION ROUTES
Route::patch('/notification/mark-as-read', [NotificationController::class, 'markAsRead'])->middleware('auth')->name('notification.mark-as-read');

Route::prefix('support')->name('support.')->group(function () {
    Route::post('/{support}/answer', [SupportsController::class, 'answer'])->name('answer');
    Route::patch('/{support}/read', [SupportsController::class, 'read'])->name('read');
});

// AUTH ROUTES
Route::middleware('auth')->group(function () {
    Route::get('/notification', [NotificationController::class, 'index'])->name('notification.index');
    Route::post('/editorjs/upload', [EditorController::class, 'upload'])->name('editorjs.upload');
    Route::get('/profile', [UserController::class, 'showMe'])->name('profile.my');
    Route::get('/logout', [SessionController::class, 'logout'])->name('logout');
    Route::get('/my-hackathons', [HackathonController::class, 'myHackathons'])->name('my-hackathons');
    Route::prefix('hackathons')->name('hackathons.')->group(function () {
        Route::post('/', [HackathonController::class, 'store'])->name('store');
        Route::prefix('/{hackathon}')->group(function () {
            Route::patch('/', [HackathonController::class, 'update'])->name('update');
            Route::post('/publish', [HackathonController::class, 'publish'])->name('publish');
            Route::post('/finish', [HackathonController::class, 'finishHackathon'])->name('finish');
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
                    Route::get('/search', [TeamController::class, 'search'])->name('search');
                    Route::prefix('/projects')->name('projects.')->group(function () {
                        Route::post('/', [ProjectsController::class, 'store'])->name('store');
                        Route::get('/showTeamProjects', [ProjectsController::class, 'showTeamProjects'])->name('show-team-projects');
                        Route::prefix('/{project}')->group(function () {
                            Route::post('/publish', [ProjectsController::class, 'publish'])->name('publish');
                        });
                    });
                });
            });
            Route::prefix('/projects')->name('projects.')->group(function () {
                Route::get('/', [ProjectsController::class, 'index'])->name('index');
                Route::prefix('/{project}')->group(function () {
                    Route::patch('/', [ProjectsController::class, 'update'])->name('update');
                    Route::delete('/delete-presentation', [ProjectsController::class, 'deletePresentation'])->name('delete-presentation');
                    Route::delete('/', [ProjectsController::class, 'destroy'])->name('destroy');
                    Route::post('/rate', [ProjectsController::class, 'rate'])->name('rate');
                });
            });

            Route::get('/gallery', [HackathonController::class, 'gallery'])->name('gallery');

            Route::prefix('/support')->name('support.')->group(function () {
                Route::get('/', [SupportsController::class, 'index'])->name('index');
                Route::post('/', [SupportsController::class, 'store'])->name('store');
            });

            Route::prefix('/staff')->name('staff.')->group(function () {
                Route::patch('/', [HackathonStaffController::class, 'update'])->name('update');
                Route::post('/kick', [HackathonStaffController::class, 'kick'])->name('kick');
                Route::post('/invite', [HackathonStaffController::class, 'createInvite'])->name('create-invite');
                Route::get('/invite/{token}', [HackathonStaffController::class, 'acceptInvite'])->name('accept-invite');
                Route::post('/inviteById', [HackathonStaffController::class, 'inviteUserById'])->name('invite-by-id');
                Route::get('/search', [HackathonStaffController::class, 'search'])->name('search');
            });

            Route::get('/download-users', [HackathonController::class, 'downloadUsers'])->name('download-users');
            Route::get('/download-report', [PdfController::class, 'protocol'])->name('download-report');
            Route::get('/certificate', [PdfController::class, 'certificate'])->name('certificate');
        });
    });
});

// MEDIA ROUTES
Route::prefix('hackathons/{hackathon}/')->name('hackathons.')->group(function () {
    Route::get('/media', [MediaController::class, 'showHackathonMedia'])->name('image');
    Route::get('/media-mobile', [MediaController::class, 'showHackathonMediaMobile'])->name('image-mobile');
    Route::get('/files/{media}', [MediaController::class, 'showHackathonFile'])->name('files.download');
    Route::prefix('/tabs/{tab}')->name('tabs.')->group(function () {
        Route::get('/partner-images', [MediaController::class, 'showHackathonPartners'])->name('partner-images');
        Route::get('/partner-image/{media}', [MediaController::class, 'showHackathonPartnerImage'])->name('partner-image');
    });
    Route::prefix('/projects/{project}')->name('projects.')->group(function () {
        Route::get('/preview', [MediaController::class, 'showProjectPreview'])->name('image');
        Route::get('/presentation', [MediaController::class, 'showProjectPresentation'])->name('presentation');
        Route::get('/presentation/{media}', [MediaController::class, 'DownloadProjectsPresentation'])->name('presentation.download');
        Route::get('/gallery', [MediaController::class, 'showProjectGallery'])->name('gallery');
        Route::get('/gallery/{mediaId}', [MediaController::class, 'showProjectGalleryImage'])->name('gallery.image');
    });
});

Route::get('/awards/{award}/media', [MediaController::class, 'showAwardMedia'])->name('awards.image');
Route::get('/banners/{banner}/media', [MediaController::class, 'showBanner'])->name('banners.image');

// ADMIN ROUTES
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::prefix('/moderation')->name('moderation.')->group(function () {
        Route::prefix('/hackathons')->name('hackathons')->group(function () {
            Route::get('/', [AdminController::class, 'moderationHackathons'])->name('index');
            Route::prefix('/{hackathon}')->group(function () {
                Route::post('/accept', [AdminController::class, 'acceptHackathon'])->name('accept');
                Route::post('/reject', [AdminController::class, 'rejectHackathon'])->name('reject');
            });
        });
        Route::prefix('/projects')->name('projects')->group(function () {
            Route::get('/', [AdminController::class, 'moderationProjectsHackathons'])->name('hackathons');
            Route::get('/hackathon/{hackathon}', [AdminController::class, 'moderationProjects'])->name('hackathon');
            Route::prefix('/{project}')->group(function () {
                Route::post('/accept', [AdminController::class, 'acceptProject'])->name('accept');
                Route::post('/reject', [AdminController::class, 'rejectProject'])->name('reject');
            });
        });
    });

    Route::prefix('/support')->name('support.')->group(function () {
        Route::get('/', [AdminController::class, 'support'])->name('index');
    });

    Route::prefix('/users')->name('users.')->group(function () {
        Route::get('/', [AdminController::class, 'users'])->name('index');
        Route::prefix('/{user}')->group(function () {
            Route::post('/block', [AdminController::class, 'blockUser'])->name('block');
            Route::post('/unblock', [AdminController::class, 'unblockUser'])->name('unblock');
            Route::post('/change-roles', [AdminController::class, 'changeRoles'])->name('change-roles')->middleware('top.admin');
        });
    });

    Route::prefix('/contents')->name('contents.')->group(function () {
        Route::prefix('/tags')->name('tags')->group(function () {
            Route::get('/', [AdminController::class, 'tags'])->name('index');
            Route::post('/', [AdminController::class, 'storeTag'])->name('store');
            Route::patch('/{tag}', [AdminController::class, 'updateTag'])->name('update');
            Route::delete('/{tag}', [AdminController::class, 'deleteTag'])->name('delete');
            Route::post('/change-order', [AdminController::class, 'changeTagOrder'])->name('change-order');
        });

        Route::prefix('/banners')->name('banners')->group(function () {
            Route::get('/', [AdminController::class, 'banners'])->name('index');
            Route::post('/', [AdminController::class, 'storeBanner'])->name('store');
            Route::patch('/{banner}', [AdminController::class, 'updateBanner'])->name('update');
            Route::delete('/{banner}', [AdminController::class, 'deleteBanner'])->name('delete');
            Route::post('/change-order', [AdminController::class, 'changeBannerOrder'])->name('change-order');
        });

        Route::prefix('/awards')->name('awards')->group(function () {
            Route::get('/', [AdminController::class, 'awards'])->name('index');
            Route::patch('/{award}', [AdminController::class, 'updateAward'])->name('update');
        });
    });

    Route::get('/roles', [AdminController::class, 'allRoles'])->name('roles');

    Route::post('/sync-user', [AdminController::class, 'syncUser'])->name('sync-user')->middleware('throttle:2,1');
    Route::post('/hackathons/finish', [AdminController::class, 'finishHackathons'])->name('hackathons.finish')->middleware('throttle:10,1');;
});

// REFBOOK ROUTES
Route::get('/refbook/roles', [AdminController::class, 'staffRoles'])->name('roles');
