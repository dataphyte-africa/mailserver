<?php

use App\Http\Controllers\Public\ElectionLocationController;
use App\Http\Controllers\Public\ObserverApplicationPageController;
use App\Http\Controllers\Public\PreferencesController;
use App\Http\Controllers\Public\SubscriptionFormController;
use App\Http\Controllers\Public\UnsubscribeController;
use App\Http\Controllers\Public\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing page
|--------------------------------------------------------------------------
*/
Route::redirect('/login', '/');
Route::redirect('/cp/auth/login', '/');

Route::get('/', function () {
    // If already authenticated in Statamic CP, send straight to CP
    if (app(\Statamic\Contracts\Auth\UserRepository::class)->current()) {
        return redirect('/cp');
    }

    // Fetch site logo from the newsletter_settings GlobalSet (editor-controlled)
    $siteLogo = null;
    try {
        $gs = \Statamic\Facades\GlobalSet::findByHandle('newsletter_settings');
        if ($gs) {
            $raw = $gs->inDefaultSite()?->data()?->get('site_logo');
            if ($raw) {
                $siteLogo = is_string($raw)
                    ? asset('storage/' . ltrim($raw, '/'))
                    : (method_exists($raw, 'url') ? $raw->url() : null);
            }
        }
    } catch (\Throwable) {
        // GlobalSet not yet scaffolded — silent fallback
    }

    return view('landing.index', compact('siteLogo'));
})->name('landing');

/*
|--------------------------------------------------------------------------
| Newsletter Public Routes
|--------------------------------------------------------------------------
*/

// Unsubscribe — signed URL, no auth required
Route::get('/unsubscribe/{token}', [UnsubscribeController::class, 'show'])
    ->name('newsletter.unsubscribe.show');

Route::post('/unsubscribe/{token}', [UnsubscribeController::class, 'process'])
    ->name('newsletter.unsubscribe.process');

// Preference center — signed URL, no auth required
Route::get('/preferences/{token}', [PreferencesController::class, 'show'])
    ->name('newsletter.preferences.show');

Route::post('/preferences/{token}', [PreferencesController::class, 'update'])
    ->name('newsletter.preferences.update');

// Public newsletter form schema + submit endpoints.
Route::get('/subscribe/{form}', [ObserverApplicationPageController::class, 'show'])
    ->name('newsletter.forms.show');

Route::get('/subscribe/{form}/schema', [SubscriptionFormController::class, 'schema'])
    ->name('newsletter.forms.schema');

Route::get('/subscribe/{form}/locations/states', [ElectionLocationController::class, 'states'])
    ->middleware('throttle:60,1')
    ->name('newsletter.forms.locations.states');

Route::get('/subscribe/{form}/locations/states/{state}/lgas', [ElectionLocationController::class, 'lgas'])
    ->middleware('throttle:60,1')
    ->name('newsletter.forms.locations.lgas');

Route::get('/subscribe/{form}/locations/lgas/{lga}/wards', [ElectionLocationController::class, 'wards'])
    ->middleware('throttle:60,1')
    ->name('newsletter.forms.locations.wards');

Route::post('/subscribe/{form}', [SubscriptionFormController::class, 'submit'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('throttle:10,1')
    ->name('newsletter.forms.submit');

// Elastic Email webhook endpoints — public, no CSRF.
// Some provider configurations send real webhook events as GET query params,
// while validator checks may hit the URL with an empty GET request.
// Keep the legacy /api path alive because earlier docs pointed there.
foreach (['/webhooks/elastic-email', '/api/webhooks/elastic-email'] as $path) {
    $route = Route::match(['get', 'post'], $path, [WebhookController::class, 'receive'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    if ($path === '/webhooks/elastic-email') {
        $route->name('newsletter.webhook.elastic-email');
    }
}
