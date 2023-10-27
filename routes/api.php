<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('get-data-users',[ApiController::class, 'getDataUsers']);
Route::get('get-data-user-groups',[ApiController::class, 'getDataUserGroups']);
Route::get('get-data-item',[ApiController::class, 'getDataItem']);
Route::get('get-data-item-unit',[ApiController::class, 'getDataItemUnit']);
Route::get('get-data-item-category',[ApiController::class, 'getDataItemCategory']);
Route::get('get-data-item-warehouse',[ApiController::class, 'getDataItemWarehouse']);
Route::get('get-data-item-barcode',[ApiController::class, 'getDataItemBarcode']);
Route::get('get-data-item-packge',[ApiController::class, 'getDataItemPackge']);
Route::get('get-data-item-stock',[ApiController::class, 'getDataItemStock']);
Route::get('get-data-item-rack',[ApiController::class, 'getDataItemRack']);
Route::post('post-data-sales-invoice',[ApiController::class, 'postDataSalesInvoice']);
Route::post('post-data-sales-invoice-item',[ApiController::class, 'postDataSalesInvoiceItem']);
Route::post('post-data-sii-remove-log',[ApiController::class, 'postDataSIIRemoveLog']);
Route::get('get-data-sales-invoice',[ApiController::class, 'getDataSalesInvoice']);
Route::get('get-data-expenditure',[ApiController::class, 'getDataExpenditure']);
Route::get('get-data-profit-loss-report',[ApiController::class, 'getDataProfitLossReport']);
Route::get('get-data-journal-voucher',[ApiController::class, 'getDataJournalVoucher']);
Route::get('get-data-core-member', [ApiController::class, 'getDataCoreMember']);
Route::post('post-data-core-member', [ApiController::class, 'postDataCoreMember']);
Route::post('post-data-login-log', [ApiController::class, 'postDataLoginLog']);
Route::post('post-data-close-cashier', [ApiController::class, 'postDataCloseCashier']);
Route::post('post-data-core-member-kopkar', [ApiController::class, 'postDataCoreMemberKopkar']);
Route::get('get-data-preference-voucher', [ApiController::class, 'getDataPreferenceVoucher']);
Route::post('post-amount-account', [ApiController::class, 'getAmountAccount']);
Route::post('post-data', [ApiController::class, 'postData']);
Route::get('get-data', [ApiController::class, 'getData']);