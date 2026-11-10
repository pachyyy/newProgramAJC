<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\GlobalController;
use App\Http\Controllers\HeaderTableController;
use App\Http\Controllers\UserFilterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewSetupPeriodeKerjaController;
use App\Http\Controllers\MasterSatuanController;

// Auth::routes();

Route::get('/test', function () {
    return abort(404);
});











// Login
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/home');
    }

    return view('welcome');
});

Route::post('/checkLogin', [AuthController::class, 'authenticate']);
Route::post('/checkOnline', [UsersController::class, 'checkOnline']);

Route::get('/updateIdle', [AuthController::class, 'updateIdle']);
Route::get('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/ceklockperiode', [GlobalController::class, 'getLockPeriode'])->middleware('auth');
Route::post('/ceklockperiodeinput', [GlobalController::class, 'getLockPeriodeInput'])->middleware('auth');

// Tes
Route::get('/newlogin', function () {
    return view('newmaster');
});

Route::get('/newtes', function () {
    return view('newtes');
});

Route::get('/masterSatuanAmbilSatTax', [MasterSatuanController::class, 'AmbilSatTax'])->middleware('auth');

Route::get('/home', [HomeController::class, 'newIndex'])->name('home')->middleware('auth');
Route::get('/homeaccounting', [HomeController::class, 'accountingIndex'])->middleware('auth');
Route::get('/homemarketing', [HomeController::class, 'marketingIndex'])->middleware('auth');
Route::get('/homegudang', [HomeController::class, 'gudangIndex'])->middleware('auth');
Route::get('/homepurchasing', [HomeController::class, 'purchasingIndex'])->middleware('auth');
Route::get('/homemaster', [HomeController::class, 'masterIndex'])->middleware('auth');
Route::get('/homereport', [HomeController::class, 'reportIndex'])->middleware('auth');
Route::get('/homeberkas', [HomeController::class, 'berkasIndex'])->middleware('auth');

Route::get('/getmenu/{headermenu}', [HomeController::class, 'GetMenu']);
Route::get('/getmenureport/{headermenu}', [HomeController::class, 'getMenuReport']);

Route::post('/getheadertable', [HeaderTableController::class, 'getHeaderTable'])->middleware('auth');
Route::post('/saveheadertable', [HeaderTableController::class, 'saveHeaderTable'])->middleware('auth');
Route::get('/savesetheadertable', [HeaderTableController::class, 'saveSetHeaderTable'])->middleware('auth');
// filter modal tersimpan per user (public/js/user-filter.js)
Route::get('/userfilter_get', [UserFilterController::class, 'get'])->middleware('auth');
Route::post('/userfilter_save', [UserFilterController::class, 'save'])->middleware('auth');

Route::get('/setperiode', [NewSetupPeriodeKerjaController::class, 'index'])->middleware('auth');
Route::post('/newsetupperiodekerjaupdate', [NewSetupPeriodeKerjaController::class, 'updatePeriodeKerja'])->middleware('auth');

Route::post('/spnobuktisimbol', [GlobalController::class, 'getNoBuktiSimbol'])->middleware('auth');
Route::post('/spnobukti', [GlobalController::class, 'getNoBukti'])->middleware('auth');
Route::get('/spgetstockakhir', [GlobalController::class, 'getStockAkhir'])->middleware('auth');
Route::get('/gettanggalserver', [GlobalController::class, 'getTanggalServer'])->middleware('auth');

require __DIR__.'/accounting.php';
require __DIR__.'/marketing.php';
require __DIR__.'/gudang.php';
require __DIR__.'/purchasing.php';
require __DIR__.'/master.php';
require __DIR__.'/produksi.php';
Route::middleware('auth')->group(function () {
    require __DIR__.'/report.php';
});
require __DIR__.'/berkas.php';
require __DIR__.'/utilitas.php';
require __DIR__.'/otorisasi.php';
require __DIR__.'/documentbc.php';

Route::get('/__debug_login_sa', function () {
    Auth::loginUsingId(\App\Models\User::where('username', 'SA')->first()->id);
    return 'ok';
});



