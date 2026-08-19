<?php

use App\Http\Controllers\Admin\ContentItemController;
use App\Http\Controllers\Admin\ContentTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\LayoutController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\OfficerController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallerController::class, 'show'])->name('install.show');
Route::post('/install', [InstallerController::class, 'install'])->middleware('throttle:5,1')->name('install.run');

Route::middleware('installed')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
    });
    Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:editor,publisher,super_admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('pages', PageController::class)->except(['show', 'destroy']);
        Route::delete('pages/{page}', [PageController::class, 'destroy'])->middleware('role:publisher,super_admin')->name('pages.destroy');
        Route::post('pages/{page}/submit', [PageController::class, 'submit'])->name('pages.submit');
        Route::post('pages/{page}/publish', [PageController::class, 'publish'])->middleware('role:publisher,super_admin')->name('pages.publish');
        Route::post('pages/{page}/reject', [PageController::class, 'reject'])->middleware('role:publisher,super_admin')->name('pages.reject');

        Route::resource('content', ContentItemController::class)->except(['show', 'destroy']);
        Route::delete('content/{content}', [ContentItemController::class, 'destroy'])->middleware('role:publisher,super_admin')->name('content.destroy');
        Route::post('content/{content}/submit', [ContentItemController::class, 'submit'])->name('content.submit');
        Route::post('content/{content}/publish', [ContentItemController::class, 'publish'])->middleware('role:publisher,super_admin')->name('content.publish');
        Route::post('content/{content}/reject', [ContentItemController::class, 'reject'])->middleware('role:publisher,super_admin')->name('content.reject');
        Route::delete('content/{content}/attachments/{attachment}', [ContentItemController::class, 'destroyAttachment'])->middleware('role:publisher,super_admin')->name('content.attachments.destroy');
        Route::post('content-types', [ContentTypeController::class, 'store'])->middleware('role:publisher,super_admin')->name('content-types.store');
        Route::put('content-types/{type}', [ContentTypeController::class, 'update'])->middleware('role:publisher,super_admin')->name('content-types.update');

        Route::resource('officers', OfficerController::class)->except(['show', 'destroy']);
        Route::delete('officers/{officer}', [OfficerController::class, 'destroy'])->middleware('role:publisher,super_admin')->name('officers.destroy');
        Route::patch('officers/{officer}/restore', [OfficerController::class, 'restore'])->middleware('role:publisher,super_admin')->name('officers.restore');
        Route::delete('officers/{officer}/force', [OfficerController::class, 'forceDestroy'])->middleware('role:publisher,super_admin')->name('officers.force-destroy');
        Route::post('officers/{officer}/submit', [OfficerController::class, 'submit'])->name('officers.submit');
        Route::post('officers/{officer}/publish', [OfficerController::class, 'publish'])->middleware('role:publisher,super_admin')->name('officers.publish');

        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::put('media/{medium}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{medium}', [MediaController::class, 'destroy'])->middleware('role:publisher,super_admin')->name('media.destroy');

        Route::get('revisions', [RevisionController::class, 'index'])->name('revisions.index');
        Route::get('revisions/{revision}', [RevisionController::class, 'show'])->name('revisions.show');
        Route::post('revisions/{revision}/submit', [RevisionController::class, 'submit'])->name('revisions.submit');
        Route::delete('revisions/{revision}', [RevisionController::class, 'destroy'])->name('revisions.destroy');

        Route::middleware('role:publisher,super_admin')->group(function (): void {
            Route::post('revisions/{revision}/approve', [RevisionController::class, 'approve'])->name('revisions.approve');
            Route::post('revisions/{revision}/reject', [RevisionController::class, 'reject'])->name('revisions.reject');

            Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
            Route::post('menus', [MenuController::class, 'store'])->name('menus.store');
            Route::put('menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
            Route::post('menus/{menu}/items', [MenuController::class, 'storeItem'])->name('menus.items.store');
            Route::put('menu-items/{item}', [MenuController::class, 'updateItem'])->name('menu-items.update');
            Route::delete('menu-items/{item}', [MenuController::class, 'destroyItem'])->name('menu-items.destroy');

            Route::get('layouts', [LayoutController::class, 'index'])->name('layouts.index');
            Route::post('layouts', [LayoutController::class, 'store'])->name('layouts.store');
            Route::put('layouts/{layout}', [LayoutController::class, 'update'])->name('layouts.update');
            Route::post('layouts/{layout}/items', [LayoutController::class, 'storeItem'])->name('layouts.items.store');
            Route::put('layout-items/{item}', [LayoutController::class, 'updateItem'])->name('layout-items.update');
            Route::delete('layout-items/{item}', [LayoutController::class, 'destroyItem'])->name('layout-items.destroy');

            Route::get('galleries', [GalleryController::class, 'index'])->name('galleries.index');
            Route::post('galleries', [GalleryController::class, 'store'])->name('galleries.store');
            Route::put('galleries/{gallery}', [GalleryController::class, 'update'])->name('galleries.update');
            Route::delete('galleries/{gallery}', [GalleryController::class, 'destroy'])->name('galleries.destroy');
            Route::post('galleries/{gallery}/items', [GalleryController::class, 'storeItem'])->name('galleries.items.store');
            Route::put('gallery-items/{item}', [GalleryController::class, 'updateItem'])->name('gallery-items.update');
            Route::delete('gallery-items/{item}', [GalleryController::class, 'destroyItem'])->name('gallery-items.destroy');

            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingController::class, 'store'])->name('settings.store');
            Route::put('settings/{setting}', [SettingController::class, 'update'])->name('settings.update');
            Route::delete('settings/{setting}', [SettingController::class, 'destroy'])->name('settings.destroy');
        });

        Route::middleware('role:super_admin')->group(function (): void {
            Route::resource('users', UserController::class)->except('show');
        });
    });

    Route::middleware('locale')->group(function () {
        Route::get('/', [SiteController::class, 'home'])->name('home');
        Route::get('/search', [SiteController::class, 'search'])->name('search');
        Route::get('/pages/{path?}', [SiteController::class, 'show'])->where('path', '.*')->name('page.show');
    });
    Route::prefix('en')->middleware('locale')->group(function () {
        Route::get('/', [SiteController::class, 'home'])->name('home.en');
        Route::get('/search', [SiteController::class, 'search'])->name('search.en');
        Route::get('/pages/{path?}', [SiteController::class, 'show'])->where('path', '.*')->name('page.show.en');
        Route::get('/{path}', [SiteController::class, 'show'])->where('path', '.+')->name('content.show.en');
    });
    Route::middleware('locale')->get('/{path}', [SiteController::class, 'show'])
        ->where('path', '(?!en(?:/|$)).+')
        ->name('content.show');
});
