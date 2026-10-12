<?php

use App\Filament\Pages\EditProfile;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HalalController;
use App\Http\Controllers\LandingpageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::feeds();
Route::controller(LandingpageController::class)->group(function () {
    Route::get('/', 'index')->name('landingpage');
    Route::get('/news', 'news')->name('news-more');
    Route::get('/news/data', 'newsData')->name('news-data');
    Route::get('/review', 'review')->name('review-more');
    Route::get('/toss/{slug}', 'toss')->name('toss');
    Route::get('/certificate/{slug}', 'certificate')->name('certificate');
    Route::get('/about-us', 'aboutUs')->name('about-us');
});

Route::controller(ArticleController::class)->group(function () {
    Route::get('/article', 'article')->name('article-more');
    Route::get('/article/data', 'articleData')->name('article-data');
    Route::get('/article/{article_slug}', 'articleDetail')->name('article-detail');
    Route::post('/article/{article_slug}/share', 'incrementShare')->name('article.increment-share');
});

Route::controller(ActivityController::class)->group(function () {
    Route::get('/activity', 'activity')->name('activity-more');
    Route::get('/activity/data', 'activityData')->name('activity-data');
    Route::get('/activity/{activity_slug}', 'activityDetail')->name('activity-detail');
    Route::post('/activity/{activity_slug}/share', 'incrementShare')->name('activity.increment-share');
});

Route::controller(EventController::class)->group(function () {
    Route::get('/event', 'event')->name('event-more');
    Route::get('/event/data', 'eventData')->name('event-data');
    Route::get('/event/{event_slug}', 'eventDetail')->name('event-detail');
});

Route::controller(PropertyController::class)->group(function () {
    Route::get('/property', 'property')->name('property-more');
    Route::get('/property/data', 'propertyData')->name('property-data');
    Route::get('/property/{property_slug}', 'propertyDetail')->name('property-detail');
});

Route::resource('halal', HalalController::class);

Route::controller(OrderController::class)->group(function () {
    Route::post('/event/midtrans/create-transaction', 'createMidtransTransaction')->name('event.midtrans.create-transaction');;
    Route::post('/event/register/free', 'registerFree')->name('event.register.free');
});

//ticket
Route::controller(TicketController::class)->group(function () {
    Route::get('ticket', 'index')->name('ticket.get');
    Route::get('ticket/getbidangticket', 'getbidang')->name('ticket.bidang');
    Route::post('ticket/search', 'search')->name('ticket.search');
    Route::get('ticket/{ticket_code}', 'detail')->name('ticket.detail');
    Route::post('ticket', 'store')->name('ticket.post');
});

Route::middleware(['guest'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('login', 'login')->name('login');
        Route::post('login', 'postLogin')->name('login.post');
        Route::get('register', 'register')->name('register');
        Route::post('register', 'postRegister')->name('register.post');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [LandingpageController::class, 'index'])->name('dashboard');
    Route::get('/edit-profile', EditProfile::class)->name('filament.pages.edit-profile');
    Route::post('/dashboard/logout', [AuthController::class, 'logout'])->name('logout');
    Route::resource('dashboard/event', EventController::class);
    Route::get('/report/download', [ReportController::class, 'download'])
        ->name('report.download');
});

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// additional
Route::get('clear', function () {
    \Artisan::call('cache:clear');
    \Artisan::call('route:clear');
    \Artisan::call('view:clear');
});

Route::get('down/{secret?}', function ($secret) {
    if ($secret === config('app.secret_maintenance')) {
        \Artisan::call('down', ['--secret' => $secret]);
        return redirect('/');
    }
    return redirect()->route('landingpage');
});

Route::get('up', function () {
    \Artisan::call('up');
    return redirect('/');
});

Route::get('/linkstorage', function () {
    \Artisan::call('storage:link');
});
