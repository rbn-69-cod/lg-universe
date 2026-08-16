<?php

use App\Http\Controllers\Admin\ExcelImportRangeController;
use App\Http\Controllers\Admin\PlataformaController as AdminPlataformaController;
use App\Http\Controllers\Admin\TutorialController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\ApiBuscarEmailController;
use App\Http\Controllers\ApiNetflixProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IAController;
use App\Http\Controllers\NetcodeBotLinkController;
use App\Http\Controllers\SecureBotLinkController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Models\Plataforma;
use App\Support\TutorialContent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::view('/', 'netcode-menu')->name('home');

Route::redirect('/netcode', '/')->name('netcode');
Route::get('/netcode/codigos', fn () => view('netcode', [
    'netcodePage' => 'codigos',
    'adminTutorials' => TutorialContent::public(),
]))->name('netcode.codigos');
Route::get('/netcode/inicio-sesion', fn () => view('netcode', [
    'netcodePage' => 'acceso4',
    'adminTutorials' => TutorialContent::public(),
]))->name('netcode.acceso4');
Route::get('/netcode/bot/acceso4', NetcodeBotLinkController::class)
    ->name('netcode.bot.acceso4');

Route::post('/api/buscar-email', [ApiBuscarEmailController::class, 'buscar'])
    ->middleware('throttle:30,1')
    ->name('api.buscar-email');

Route::post('/api/netflix-validar', [ApiNetflixProfileController::class, 'validateAccess'])
    ->middleware('throttle:30,1')
    ->name('api.netflix-validar');

Route::post('/ia-chat', [IAController::class, 'chat'])
    ->middleware('throttle:20,1');

Route::get('/tutorial-media/{path}', function (string $path) {
    abort_unless(\Illuminate\Support\Facades\Storage::disk('public')->exists($path), 404);

    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
})->where('path', '.*')->name('tutorial-media');

Route::get('/payment-media/{path}', function (string $path) {
    abort_unless(\Illuminate\Support\Facades\Storage::disk('public')->exists($path), 404);

    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
})->where('path', '.*')->name('payment-media');

Route::get('/plataformas', function () {
    $plataformas = Plataforma::all();

    return view('plataformas', compact('plataformas'));
});

Route::view('/pago', 'pago')->name('pago');

Route::get('/dashboard', fn () => redirect(rtrim((string) env('FRONTEND_URL', 'http://localhost:4200'), '/').'/dashboard'))
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard');

Route::get('/legacy/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard.legacy');

Route::get('/cron/procesar-emails', function () {
    $tokenRequest = (string) request()->query('token', '');
    $tokenEnv = trim((string) env('CRON_TOKEN', ''));

    if ($tokenEnv === '' || $tokenRequest === '' || ! hash_equals($tokenEnv, $tokenRequest)) {
        Log::warning('Intento de acceso al cron con token invalido', [
            'ip' => request()->ip(),
        ]);

        abort(403, 'Acceso denegado');
    }

    Artisan::call('emails:procesar-pedidos');

    Log::info('Cron procesar-emails ejecutado via HTTP');

    return response()->json([
        'status' => 'ok',
        'ran_at' => now()->toDateTimeString(),
    ]);
})->name('cron.procesar-emails');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/api/v1/dashboard', [DashboardApiController::class, 'show'])
        ->name('api.v1.dashboard.show');
    Route::match(['get', 'post'], '/api/v1/dashboard/logout', [DashboardApiController::class, 'logout'])
        ->name('api.v1.dashboard.logout');
    Route::post('/api/v1/dashboard/excel-sync', [DashboardApiController::class, 'syncExcel'])
        ->name('api.v1.dashboard.excel-sync');
    Route::post('/api/v1/dashboard/imap-run', [DashboardApiController::class, 'runImap'])
        ->name('api.v1.dashboard.imap-run');
    Route::post('/api/v1/dashboard/imap-test', [DashboardApiController::class, 'testImapConnection'])
        ->name('api.v1.dashboard.imap-test');
    Route::put('/api/v1/dashboard/imap-settings', [DashboardApiController::class, 'updateImapSettings'])
        ->name('api.v1.dashboard.imap-settings.update');
    Route::put('/api/v1/dashboard/payment-settings', [DashboardApiController::class, 'updatePaymentSettings'])
        ->name('api.v1.dashboard.payment-settings.update');
    Route::post('/api/v1/dashboard/payment-settings/{method}/qr', [DashboardApiController::class, 'uploadPaymentQr'])
        ->name('api.v1.dashboard.payment-settings.qr');
    Route::post('/api/v1/dashboard/tutorials/{key}', [DashboardApiController::class, 'updateTutorial'])
        ->name('api.v1.dashboard.tutorials.update');
    Route::delete('/api/v1/dashboard/tutorials/{key}/media', [DashboardApiController::class, 'removeTutorialMedia'])
        ->name('api.v1.dashboard.tutorials.media.destroy');
    Route::post('/api/v1/dashboard/catalog', [DashboardApiController::class, 'storeCatalogPlatform'])
        ->name('api.v1.dashboard.catalog.store');
    Route::put('/api/v1/dashboard/catalog/{plataforma}', [DashboardApiController::class, 'updateCatalogPlatform'])
        ->name('api.v1.dashboard.catalog.update');
    Route::delete('/api/v1/dashboard/catalog/{plataforma}', [DashboardApiController::class, 'destroyCatalogPlatform'])
        ->name('api.v1.dashboard.catalog.destroy');
    Route::patch('/api/v1/dashboard/catalog/{plataforma}/{direction}', [DashboardApiController::class, 'moveCatalogPlatform'])
        ->name('api.v1.dashboard.catalog.move');
    Route::post('/api/v1/dashboard/admins', [DashboardApiController::class, 'storeAdmin'])
        ->name('api.v1.dashboard.admins.store');
    Route::put('/api/v1/dashboard/admins/{user}', [DashboardApiController::class, 'updateAdmin'])
        ->name('api.v1.dashboard.admins.update');
    Route::delete('/api/v1/dashboard/admins/{user}', [DashboardApiController::class, 'destroyAdmin'])
        ->name('api.v1.dashboard.admins.destroy');
    Route::post('/api/v1/dashboard/imported-data/clear', [DashboardApiController::class, 'clearImportedData'])
        ->name('api.v1.dashboard.imported-data.clear');
    Route::post('/api/v1/dashboard/excel-ranges', [DashboardApiController::class, 'storeRange'])
        ->name('api.v1.dashboard.excel-ranges.store');
    Route::put('/api/v1/dashboard/excel-ranges/{excelImportRange}', [DashboardApiController::class, 'updateRange'])
        ->name('api.v1.dashboard.excel-ranges.update');
    Route::delete('/api/v1/dashboard/excel-ranges/{excelImportRange}', [DashboardApiController::class, 'destroyRange'])
        ->name('api.v1.dashboard.excel-ranges.destroy');
    Route::put('/api/v1/dashboard/accounts/{cuenta}/bot-links', [DashboardApiController::class, 'updateAccountBotLinks'])
        ->name('api.v1.dashboard.accounts.bot-links.update');
    Route::get('/bot-links/abrir', SecureBotLinkController::class)
        ->name('bot-links.open');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(
                    Features::twoFactorAuthentication(),
                    'confirmPassword'
                ),
                ['password.confirm'],
                [],
            )
        )
        ->name('two-factor.show');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('plataformas', AdminPlataformaController::class)->except(['show']);

    Route::get('tutoriales', [TutorialController::class, 'index'])
        ->name('tutoriales.index');
    Route::put('tutoriales/{key}', [TutorialController::class, 'update'])
        ->name('tutoriales.update');
    Route::delete('tutoriales/{key}/media', [TutorialController::class, 'removeMedia'])
        ->name('tutoriales.media.destroy');

    Route::get('excel-import-ranges', [ExcelImportRangeController::class, 'index'])
        ->name('excel-import-ranges.index');
    Route::post('excel-import-ranges', [ExcelImportRangeController::class, 'store'])
        ->name('excel-import-ranges.store');
    Route::put('excel-import-ranges/{excelImportRange}', [ExcelImportRangeController::class, 'update'])
        ->name('excel-import-ranges.update');
    Route::patch('excel-import-ranges/{excelImportRange}/toggle', [ExcelImportRangeController::class, 'toggle'])
        ->name('excel-import-ranges.toggle');
    Route::delete('excel-import-ranges/{excelImportRange}', [ExcelImportRangeController::class, 'destroy'])
        ->name('excel-import-ranges.destroy');
    Route::post('excel-import-ranges/sync', [ExcelImportRangeController::class, 'sync'])
        ->name('excel-import-ranges.sync');

    Route::get('plataformas/{id}/subir', [AdminPlataformaController::class, 'subir'])
        ->name('plataformas.subir');

    Route::get('plataformas/{id}/bajar', [AdminPlataformaController::class, 'bajar'])
        ->name('plataformas.bajar');
});
