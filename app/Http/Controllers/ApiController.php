<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcctAccount;
use App\Models\AcctAccountSetting;
use App\Models\AcctCreditAccount;
use App\Models\AcctProfitLossReport;
use App\Models\CloseCashierLog;
use App\Models\CoreEmployee;
use App\Models\CoreMember;
use App\Models\Expenditure;
use App\Models\InvtItem;
use App\Models\InvtItemBarcode;
use App\Models\InvtItemCategory;
use App\Models\InvtItemPackge;
use App\Models\InvtItemRack;
use App\Models\InvtItemStock;
use App\Models\InvtItemUnit;
use App\Models\InvtWarehouse;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\PreferenceTransactionModule;
use App\Models\PreferenceVoucher;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SIIRemoveLog;
use App\Models\SystemLoginLog;
use App\Models\SystemUserGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    public function getDataItem()
    {
        $data = InvtItem::get();

        return json_encode($data);
    }

    public function getDataItemUnit()
    {
        $data = InvtItemUnit::get();

        return json_encode($data);
    }

    public function getDataItemCategory()
    {
        $data = InvtItemCategory::get();

        return json_encode($data);
    }

    public function getDataItemWarehouse()
    {
        $data = InvtWarehouse::get();

        return json_encode($data);
    }

    public function getDataItemBarcode()
    {
        $data = InvtItemBarcode::get();

        return json_encode($data);
    }

    public function getDataItemPackge()
    {
        $data = InvtItemPackge::get();

        return json_encode($data);
    }

    public function getDataItemStock()
    {
        $data = InvtItemStock::get();

        return json_encode($data);
    }

    public function postDataSalesInvoice(Request $request)
    {
        $transaction_module_code = 'PJL';
        $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);



        $data_journal = array(
            'company_id'                    => $request['company_id'],
            'journal_voucher_status'        => 1,
            'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
            'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
            'transaction_module_id'         => $transaction_module_id,
            'transaction_module_code'       => $transaction_module_code,
            'journal_voucher_date'          => $request['sales_invoice_date'],
            'transaction_journal_no'        => $request['sales_invoice_no'],
            'journal_voucher_period'        => date('Ym', strtotime($request['sales_invoice_date'])),
            'journal_voucher_segment'       => 2,
            'updated_id'                    => $request['updated_id'],
            'created_id'                    => $request['created_id']
        );
        JournalVoucher::create($data_journal);

        if ($request['sales_payment_method'] == 1) {
            $account_setting_name = 'sales_cash_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
            if ($account_setting_status == 0) {
                $debit_amount = $request['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $request['total_amount'];
            }
            $journal_debit = array(
                'company_id'                    => $request['company_id'],
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $request['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => $request['updated_id'],
                'created_id'                    => $request['created_id']
            );
            JournalVoucherItem::create($journal_debit);

            $account_setting_name = 'sales_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
            if ($account_setting_status == 0) {
                $debit_amount = $request['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $request['total_amount'];
            }
            $journal_credit = array(
                'company_id'                    => $request['company_id'],
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $request['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => $request['updated_id'],
                'created_id'                    => $request['created_id']
            );
            JournalVoucherItem::create($journal_credit);
        } else if ($request['sales_payment_method'] == 2) {
            $account_setting_name = 'employee_debt_receivables';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
            if ($account_setting_status == 0) {
                $debit_amount = $request['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $request['total_amount'];
            }
            $journal_debit = array(
                'company_id'                    => $request['company_id'],
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $request['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => $request['updated_id'],
                'created_id'                    => $request['created_id']
            );
            JournalVoucherItem::create($journal_debit);

            $account_setting_name = 'employee_debt';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
            if ($account_setting_status == 0) {
                $debit_amount = $request['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $request['total_amount'];
            }
            $journal_credit = array(
                'company_id'                    => $request['company_id'],
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $request['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => $request['updated_id'],
                'created_id'                    => $request['created_id']
            );
            JournalVoucherItem::create($journal_credit);
        } else {
            $account_setting_name = 'sales_cashless_cash_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
            if ($account_setting_status == 0) {
                $debit_amount = $request['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $request['total_amount'];
            }
            $journal_debit = array(
                'company_id'                    => $request['company_id'],
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $request['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => $request['updated_id'],
                'created_id'                    => $request['created_id']
            );
            JournalVoucherItem::create($journal_debit);

            $account_setting_name = 'sales_cashless_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
            if ($account_setting_status == 0) {
                $debit_amount = $request['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $request['total_amount'];
            }
            $journal_credit = array(
                'company_id'                    => $request['company_id'],
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $request['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => $request['updated_id'],
                'created_id'                    => $request['created_id']
            );
            JournalVoucherItem::create($journal_credit);
        }

        if ($request['data_state'] == 1) {
            $transaction_module_code = 'HPSPJL';
            $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);
            $journal = array(
                'company_id'                    => $request['company_id'],
                'journal_voucher_status'        => 1,
                'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
                'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
                'transaction_module_id'         => $transaction_module_id,
                'transaction_module_code'       => $transaction_module_code,
                'transaction_journal_no'        => $request['sales_invoice_no'],
                'journal_voucher_date'          => $request['sales_invoice_date'],
                'journal_voucher_period'        => date('Ym', strtotime($request['sales_invoice_date'])),
                'journal_voucher_segment'       => 2,
                'updated_id'                    => $request['updated_id'],
                'created_id'                    => $request['created_id']
            );
            JournalVoucher::create($journal);
            if ($request['sales_payment_method'] == 1) {
                $account_setting_name = 'sales_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
                if ($account_setting_status == 0) {
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => $request['company_id'],
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => $request['updated_id'],
                    'created_id'                    => $request['created_id']
                );
                JournalVoucherItem::create($journal_debit);

                $account_setting_name = 'sales_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
                if ($account_setting_status == 1) {
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => $request['company_id'],
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => $request['updated_id'],
                    'created_id'                    => $request['created_id']
                );
                JournalVoucherItem::create($journal_credit);
            } else if ($request['sales_payment_method'] == 2) {
                $account_setting_name = 'sales_cash_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
                if ($account_setting_status == 0) {
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => $request['company_id'],
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => $request['updated_id'],
                    'created_id'                    => $request['created_id']
                );
                JournalVoucherItem::create($journal_debit);

                $account_setting_name = 'sales_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
                if ($account_setting_status == 1) {
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => $request['company_id'],
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => $request['updated_id'],
                    'created_id'                    => $request['created_id']
                );
                JournalVoucherItem::create($journal_credit);
            } else {
                $account_setting_name = 'sales_cashless_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
                if ($account_setting_status == 0) {
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => $request['company_id'],
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => $request['updated_id'],
                    'created_id'                    => $request['created_id']
                );
                JournalVoucherItem::create($journal_debit);

                $account_setting_name = 'sales_cashless_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', $request['company_id'])->first();
                if ($account_setting_status == 1) {
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => $request['company_id'],
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => $request['updated_id'],
                    'created_id'                    => $request['created_id']
                );
                JournalVoucherItem::create($journal_credit);
            }
        }



        //    $datacoremember = CoreEmployee::select('employee_id','debt_limit','amount_debt','remaining_limit')
        //     ->where('employee_id', $request->employee_id)
        //     ->first();

        //         //update limit hutang
        //         if($datacoremember)
        //         {
        //             $debt_limit =   (int)$datacoremember['debt_limit'];

        //             $amount_debt = (int)$datacoremember['amount_debt']  +  (int)$request['paid_amount']; 

        //             $datacoremember->amount_debt = $amount_debt ;   
        //                     $datacoremember->save();
        //         }

        $data = array(
            'customer_id'               => $request->customer_id,
            'voucher_id'                => $request->voucher_id,
            'voucher_amount'            => $request->voucher_amount,
            'voucher_no'                => $request->voucher_no,
            'sales_invoice_date'        => $request->sales_invoice_date,
            // 'employee_id'               => $request->employee_id,
            'sales_payment_method'      => $request->sales_payment_method,
            'subtotal_item'             => $request->subtotal_item,
            'subtotal_amount'           => $request->subtotal_amount,
            'discount_percentage_total' => $request->discount_percentage_total,
            'discount_amount_total'     => $request->discount_amount_total,
            // 'tax_ppn_percentage'        => $request->tax_ppn_percentage,
            // 'tax_ppn_amount'            => $request->tax_ppn_amount,
            'total_amount'              => $request->total_amount,
            'paid_amount'               => $request->paid_amount,
            // 'sales_invoice_unit_status' => $request->sales_invoice_unit_status,
            'from_store'                => $request->from_store,
            'change_amount'             => $request->change_amount,
            'company_id'                => $request->company_id,
            'created_id'                => $request->created_id,
            'updated_id'                => $request->updated_id
        );

        SalesInvoice::create($data);
        return $data;
    }

    public function getAccountDefaultStatus($account_id)
    {
        $data = AcctAccount::where('account_id', $account_id)->first();

        return $data['account_default_status'];
    }

    public function getAccountSettingStatus($account_setting_name)
    {
        $data = AcctAccountSetting::where('account_setting_name', $account_setting_name)
            ->first();

        return $data['account_setting_status'];
    }

    public function getAccountId($account_setting_name)
    {
        $data = AcctAccountSetting::where('account_setting_name', $account_setting_name)
            ->first();

        return $data['account_id'];
    }

    public function getTransactionModuleID($transaction_module_code)
    {
        $data = PreferenceTransactionModule::where('transaction_module_code', $transaction_module_code)->first();

        return $data['transaction_module_id'];
    }

    public function getTransactionModuleName($transaction_module_code)
    {
        $data = PreferenceTransactionModule::where('transaction_module_code', $transaction_module_code)->first();

        return $data['transaction_module_name'];
    }

    public function postDataSalesInvoiceItem(Request $request)
    {
        $data_packge = InvtItemPackge::where('company_id', $request['company_id'])
            ->where('item_id', $request['item_id'])
            ->where('item_unit_id', $request['item_unit_id'])
            ->where('item_category_id', $request['item_category_id'])
            ->first();

        $data_stock = InvtItemStock::where('company_id', $request['company_id'])
            ->where('item_id', $request['item_id'])
            ->where('item_unit_id', $request['item_unit_id'])
            ->where('item_category_id', $request['item_category_id'])
            ->first();

        if (isset($data_stock) && ($request['data_state'] == 0)) {
            $table = InvtItemStock::findOrFail($data_stock['item_stock_id']);
            $table->last_balance = $data_stock['last_balance'] - ($request['quantity'] * $data_packge['item_default_quantity']);
            // $table->updated_id = $request['updated_id'];
            $table->save();
        }

        // $data_sales = SalesInvoiceItem::where('company_id',$request['company_id'])
        // ->where('sales_invoice_item_id')
        // ->where('sales_invoice_id', $request['sales_invoice_id'])
        // ->first();

        // $data_sales1 = SalesInvoice::where('company_id',$request['company_id'])
        // ->where('sales_invoice_id', $request['sales_invoice_id'])
        // ->first();


        $sales_invoice_id = SalesInvoice::where('company_id', $request['company_id'])
            ->where('sales_invoice_id', $request['sales_invoice_id'])
            ->first();

        if (isset($sales_invoice_id) && ($request['data_state'] == 0)) {
            $table = SalesInvoiceItem::findOrFail($sales_invoice_id['sales_invoice_id']);
            $table->sales_invoice_id =  $sales_invoice_id['sales_invoice_id'];
            $table->save();
        }


        $data = array(
            'sales_invoice_id'                  => $sales_invoice_id['sales_invoice_id'],
            'item_category_id'                  => $request->item_category_id,
            'item_unit_id'                      => $request->item_unit_id,
            'item_id'                           => $request->item_id,
            'quantity'                          => $request->quantity,
            'item_unit_price'                   => $request->item_unit_price,
            'subtotal_amount'                   => $request->subtotal_amount_after_discount,
            'discount_percentage'               => $request->discount_percentage,
            'discount_amount'                   => $request->discount_amount,
            'subtotal_amount_after_discount'    => $request->subtotal_amount_after_discount,
            'company_id'                        => $request->company_id,
            'created_id'                        => $request->created_id,
            'updated_id'                        => $request->updated_id
        );
        // echo json_encode($data);exit;
        SalesInvoiceItem::create($data);

        // return $request->all();
    }

    // public function getDataSalesInvoice()
    // {
    //     $data = SalesInvoice::where('sales_invoice.data_state',0)
    //     ->get();

    //     return json_encode($data);
    // }

    public function getDataExpenditure()
    {
        $data = Expenditure::where('data_state', 0)
            ->get();

        return json_encode($data);
    }

    public function getDataProfitLossReport()
    {
        $data = AcctProfitLossReport::where('data_state', 0)
            ->get();

        return json_encode($data);
    }

    public function getDataJournalVoucher()
    {
        $data = JournalVoucher::join('acct_journal_voucher_item', 'acct_journal_voucher_item.journal_voucher_id', 'acct_journal_voucher.journal_voucher_id')
            ->where('acct_journal_voucher.data_state', 0)
            ->get();

        return json_encode($data);
    }

    // public function getDataCoreEmployee()
    // {


    //     $core_member = CoreEmployee::where('data_state',0)
    //     ->get();

    //     $core_member = CoreEmployee::get();

    //     return json_encode($core_member);
    // }

    // public function postDataCoreEmployee(Request $request)
    // {

    //     $data_member = CoreEmployee::where('employee_number',$request->employee_number)
    //     ->first();

    //     $data = CoreEmployee::where('employee_number',$request->employee_number)
    //     ->update(['amount_debt' => $data_member['amount_debt'] + $request->amount_debt]);

    //     return $data;
    // }

    public function getDataItemRack()
    {
        $data = InvtItemRack::get();

        return json_encode($data);
    }


    public function getDataPreferenceVoucher()
    {
        $data = PreferenceVoucher::get();

        return json_encode($data);
    }

    public function postDataLoginLog(Request $request)
    {
        $data_login = array(
            'user_id'       => $request['user_id'],
            'company_id'    => $request['company_id'],
            'log_time'      => $request['log_time'],
            'log_status'    => $request['[log_status'],
            'status_upload' => $request['status_upload'],
            'created_at'    => $request['created_at'],
            'updated_at'    => $request['updated_at'],
        );
        $data = SystemLoginLog::create($data_login);

        return $data;
    }

    public function postDataCloseCashier(Request $request)
    {
        $data_close_cashier = array(
            'company_id'                    => $request['company_id'],
            'cashier_log_date'              => $request['cashier_log_date'],
            'shift_cashier'                 => $request['shift_cashier'],
            'total_cash_transaction'        => $request['total_cash_transaction'],
            'amount_cash_transaction'       => $request['amount_cash_transaction'],
            'total_receivable_transaction'  => $request['total_receivable_transaction'],
            'amount_receivable_transaction' => $request['amount_receivable_transaction'],
            'total_cashless_transaction'    => $request['total_cashless_transaction'],
            'amount_cashless_transaction'   => $request['amount_cashless_transaction'],
            'total_transaction'             => $request['total_transaction'],
            'total_amount'                  => $request['total_amount'],
            'status_upload'                 => 0,
            'data_state'                    => $request['data_state'],
            'created_id'                    => $request['created_id'],
            'updated_id'                    => $request['updated_id'],
            'created_at'                    => $request['created_at'],
            'updated_at'                    => $request['updated_at'],
        );
        $data = CloseCashierLog::create($data_close_cashier);

        return $data;
    }

    public function getDataUsers()
    {
        $data = User::get();

        return $data;
    }

    public function getDataUserGroups()
    {
        $data = SystemUserGroup::get();

        return $data;
    }

    public function getAmountAccount(Request $request)
    {
        if ($request->profit_loss_report_type == 1) {
            $data = JournalVoucher::join('acct_journal_voucher_item', 'acct_journal_voucher_item.journal_voucher_id', 'acct_journal_voucher.journal_voucher_id')
                ->select('acct_journal_voucher_item.journal_voucher_amount', 'acct_journal_voucher_item.account_id_status')
                ->whereMonth('acct_journal_voucher.journal_voucher_date', '>=', $request->month_start)
                ->whereMonth('acct_journal_voucher.journal_voucher_date', '<=', $request->month_end)
                ->whereYear('acct_journal_voucher.journal_voucher_date', $request->year)
                ->where('acct_journal_voucher.data_state', 0)
                ->where('acct_journal_voucher_item.account_id', $request->account_id)
                ->get();
            $data_first = JournalVoucher::join('acct_journal_voucher_item', 'acct_journal_voucher_item.journal_voucher_id', 'acct_journal_voucher.journal_voucher_id')
                ->select('acct_journal_voucher_item.account_id_status')
                ->whereMonth('acct_journal_voucher.journal_voucher_date', '>=', $request->month_start)
                ->whereMonth('acct_journal_voucher.journal_voucher_date', '<=', $request->month_end)
                ->whereYear('acct_journal_voucher.journal_voucher_date', $request->year)
                ->where('acct_journal_voucher.data_state', 0)
                ->where('acct_journal_voucher_item.account_id', $request->account_id)
                ->first();

            $amount = 0;
            $amount1 = 0;
            $amount2 = 0;
            foreach ($data as $key => $val) {

                if ($val['account_id_status'] == $data_first['account_id_status']) {
                    $amount1 += $val['journal_voucher_amount'];
                } else {
                    $amount2 += $val['journal_voucher_amount'];
                }
                $amount = $amount1 - $amount2;
            }

            return $amount;
        } else if ($request->profit_loss_report_type == 2) {
            $data = JournalVoucher::join('acct_journal_voucher_item', 'acct_journal_voucher_item.journal_voucher_id', 'acct_journal_voucher.journal_voucher_id')
                ->select('acct_journal_voucher_item.journal_voucher_amount', 'acct_journal_voucher_item.account_id_status')
                ->whereYear('acct_journal_voucher.journal_voucher_date', $request->year)
                ->where('acct_journal_voucher.data_state', 0)
                ->where('acct_journal_voucher_item.account_id', $request->account_id)
                ->get();
            $data_first = JournalVoucher::join('acct_journal_voucher_item', 'acct_journal_voucher_item.journal_voucher_id', 'acct_journal_voucher.journal_voucher_id')
                ->select('acct_journal_voucher_item.account_id_status')
                ->whereYear('acct_journal_voucher.journal_voucher_date', $request->year)
                ->where('acct_journal_voucher.data_state', 0)
                ->where('acct_journal_voucher_item.account_id', $request->account_id)
                ->first();

            $amount = 0;
            $amount1 = 0;
            $amount2 = 0;
            foreach ($data as $key => $val) {

                if ($val['account_id_status'] == $data_first['account_id_status']) {
                    $amount1 += $val['journal_voucher_amount'];
                } else {
                    $amount2 += $val['journal_voucher_amount'];
                }
                $amount = $amount1 - $amount2;
            }

            return $amount;
        }
    }

    public function postDataSIIRemoveLog(Request $request)
    {
        $removeLog = array(
            'sii_remove_log_id'         => $request['sii_remove_log_id'],
            'company_id'                => $request['company_id'],
            'sales_invoice_id'          => $request['sales_invoice_id'],
            'sales_invoice_item_id'     => $request['sales_invoice_item_id'],
            'sales_invoice_no'          => $request['sales_invoice_no'],
            'sii_amount'                => $request['sii_amount'],
            'data_state'                => $request['data_state'],
            'created_id'                => $request['created_id'],
            'updated_id'                => $request['updated_id'],
            'created_at'                => $request['created_at'],
            'updated_at'                => $request['updated_at'],
        );

        $data = SIIRemoveLog::create($removeLog);

        return $data;
    }

    public function getItemUnitCost($item_id)
    {
        $item_unit_cost = InvtItemPackge::select('*')
        ->where('item_id',$item_id)
        ->first();
        return $item_unit_cost['item_unit_cost'];
    }

    public function postData(Request $request)
    {
        DB::beginTransaction();
        try {

            // chasier log
            foreach ($request->closeCashier as $key => $val) {
                $data = array(
                    'company_id'                    => $val['company_id'],
                    "cashier_log_date"              => $val['cashier_log_date'],
                    "shift_cashier"                 => $val['shift_cashier'],
                    "total_cash_transaction"        => $val['total_cash_transaction'],
                    "amount_cash_transaction"       => $val['amount_cash_transaction'],
                    "total_receivable_transaction"  => $val['total_receivable_transaction'],
                    "amount_receivable_transaction" => $val['amount_receivable_transaction'],
                    "total_cashless_transaction"    => $val['total_cashless_transaction'],
                    "amount_cashless_transaction"   => $val['amount_cashless_transaction'],
                    "total_transaction"             => $val['total_transaction'],
                    "total_amount"                  => $val['total_amount'],
                    "status_upload"                 => $val['status_upload'],
                    'data_state'                    => $val['data_state'],
                    'created_id'                    => $val['created_id'],
                    'updated_id'                    => $val['updated_id'],
                    'created_at'                    => $val['created_at'],
                    'updated_at'                    => $val['updated_at'],
                );
                CloseCashierLog::insert($data);
            }
            //sales invoice
            foreach ($request->sales as $key => $val) {
                // sales_invoice_id is important
                $data = [
                    // 'sales_invoice_id'          => $val['sales_invoice_id'],
                    'company_id'                => $val['company_id'],
                    "customer_id"               => $val['customer_id'],
                    "voucher_id"                => $val['voucher_id'],
                    "voucher_no"                => $val['voucher_no'],
                    'sales_invoice_no'          => $val['sales_invoice_no'],
                    "sales_invoice_date"        => $val['sales_invoice_date'],
                    "sales_payment_method"      => $val['sales_payment_method'],
                    "subtotal_item"             => $val['subtotal_item'],
                    "subtotal_amount"           => $val['subtotal_amount'],
                    "voucher_amount"            => $val['voucher_amount'],
                    "discount_percentage_total" => $val['discount_percentage_total'],
                    "discount_amount_total"     => $val['discount_amount_total'],
                    // "tax_ppn_percentage"        => $val['tax_ppn_percentage'],
                    // "tax_ppn_amount"            => $val['tax_ppn_amount'],
                    "total_amount"              => $val['total_amount'],
                    "paid_amount"               => $val['paid_amount'],
                    "change_amount"             => $val['change_amount'],
                    "payment_method"            => $val['payment_method'],
                    "status_upload"             => $val['status_upload'],
                    // "sales_invoice_unit_status" => $val['sales_invoice_unit_status'],
                    'data_state'                => $val['data_state'],
                    "from_store"                => $val['from_store'],
                    'created_id'                => $val['created_id'],
                    'updated_id'                => $val['updated_id'],
                    'created_at'                => $val['created_at'],
                    'updated_at'                => $val['updated_at'],
                    'tempo'                     => $val['tempo'],
                    'purchase_invoice_no'       => $val['purchase_invoice_no'],
                    'dump_id'                   => $val['sales_invoice_id'],
                ];
                SalesInvoice::insert($data);
            }
            //sales invoice item

            foreach ($request->salesItem as $key => $val) {
                $sales_invoice_id   = SalesInvoice::orderBy('created_at', 'DESC')->where('company_id', $val['company_id'])->first();
                //sales_invoice_item_id is important
                $data = array(
                    // 'sales_invoice_item_id'           => $val['sales_invoice_item_id'],
                    'company_id'                      => $val['company_id'],
                    'sales_invoice_id'                => $val['sales_invoice_id'],
                    "item_category_id"                => $val['item_category_id'],
                    "item_unit_id"                    => $val['item_unit_id'],
                    "item_id"                         => $val['item_id'],
                    "quantity"                        => $val['quantity'],
                    "item_unit_price"                 => $val['item_unit_price'],
                    "item_unit_cost"                  => $this->getItemUnitCost($val['item_id']),
                    "subtotal_amount"                 => $val['subtotal_amount'],
                    "discount_percentage"             => $val['discount_percentage'],
                    "discount_amount"                 => $val['discount_amount'],
                    "subtotal_amount_after_discount"  => $val['subtotal_amount_after_discount'],
                    "item_remark"                     => $val['item_remark'],
                    "status_upload"                   => $val['status_upload'],
                    'sales_tax_amount'                => $val['sales_tax_amount'],
                    'data_state'                      => $val['data_state'],
                    'created_id'                      => $val['created_id'],
                    'updated_id'                      => $val['updated_id'],
                    'created_at'                      => $val['created_at'],
                    'updated_at'                      => $val['updated_at'],
                    'bkp'                             => $val['bkp'],
                    'dump_id'                         => $val['sales_invoice_id'],

                );
                SalesInvoiceItem::insert($data);
            }
            // UPDATE sales inv ITEM ID 
            DB::table('sales_invoice_item as a')
                ->join('sales_invoice as c', 'a.dump_id', '=', 'c.dump_id')
                ->update(['a.sales_invoice_id' => DB::raw("`c`.`sales_invoice_id`")]);


            //sii remove log
            foreach ($request->salesRemove as $key => $val) {
                $data = array(
                    'company_id'            => $val['company_id'],
                    'sales_invoice_id'      => $val['sales_invoice_id'],
                    'sales_invoice_item_id' => $val['sales_invoice_item_id'],
                    'sales_invoice_no'      => $val['sales_invoice_no'],
                    'sii_amount'            => $val['sii_amount'],
                    'data_state'            => $val['data_state'],
                    'created_id'            => $val['created_id'],
                    'updated_id'            => $val['updated_id'],
                    'created_at'            => $val['created_at'],
                    'updated_at'            => $val['updated_at'],
                );

                SIIRemoveLog::insert($data);
            }


            //ACC Credit
            foreach ($request->accCredit as $key => $val) {
                // sales_invoice_id is important

                // AcctCreditAccount::insert($data);
                DB::connection('mysql2')->table('ciptaprocpanel_kopkar_menjanganenam_online.acct_credits_account')
                    ->insert([
                        'branch_id'   =>  $val['branch_id'],
                        'credits_id'   =>  $val['credits_id'],
                        'member_id'   =>  $val['member_id'],
                        'office_id'   =>  $val['office_id'],
                        'payment_preference_id'   =>  $val['payment_preference_id'],
                        'payment_type_id'   =>  $val['payment_type_id'],
                        'credits_payment_period'   =>  $val['credits_payment_period'],
                        'savings_account_id'   =>  $val['savings_account_id'],
                        'source_fund_id'   =>  $val['source_fund_id'],
                        'credits_account_date'   =>  $val['credits_account_date'],
                        'credits_account_due_date'   =>  $val['credits_account_due_date'],
                        'credits_account_period'   =>  $val['credits_account_period'],
                        'credits_account_type'   =>  $val['credits_account_type'],
                        'credits_account_payment_period'   =>  $val['credits_account_payment_period'],
                        'credits_account_amount'   =>  $val['credits_account_amount'],
                        'credits_account_interest'   =>  $val['credits_account_interest'],
                        'credits_account_interest_1'   => $val['credits_account_interest_1'],
                        'credits_account_interest_2'   =>  $val['credits_account_interest_2'],
                        'credits_account_adm_cost'   => $val['credits_account_adm_cost'],
                        'credits_account_provisi'   => $val['credits_account_provisi'],
                        'credits_account_komisi'   =>  $val['credits_account_komisi'],
                        'credits_account_insurance'   =>  $val['credits_account_insurance'],
                        'credits_account_remark'   =>  $val['credits_account_remark'],
                        'credits_account_bank_name'   => $val['credits_account_bank_name'],
                        'credits_account_bank_account'   => $val['credits_account_bank_account'],
                        'credits_account_bank_owner'   => $val['credits_account_bank_owner'],
                        'credits_account_materai'   => $val['credits_account_materai'],
                        'credits_account_risk_reserve'   => $val['credits_account_risk_reserve'],
                        'credits_account_stash'   => $val['credits_account_stash'],
                        'credits_account_special'   => $val['credits_account_special'],
                        'credits_account_agunan'   => $val['credits_account_agunan'],
                        'credits_account_notaris'   => $val['credits_account_notaris'],
                        'credits_account_amount_received'   =>  $val['credits_account_amount_received'],
                        'credits_account_principal_amount'   => $val['credits_account_principal_amount'],
                        'credits_account_interest_amount'   => $val['credits_account_interest_amount'],
                        'credits_account_payment_amount'   => $val['credits_account_payment_amount'],
                        'credits_account_last_balance'   => $val['credits_account_last_balance'],
                        'credits_account_interest_last_balance'   => $val['credits_account_interest_last_balance'],
                        'credits_account_payment_to'   =>  $val['credits_account_payment_to'],
                        'credits_account_payment_date'   =>   $val['credits_account_payment_date'],
                        'credits_account_last_payment_date'   =>   $val['credits_account_last_payment_date'],
                        'credits_account_accumulated_fines'   => $val['credits_account_accumulated_fines'],
                        'credits_account_used'   => $val['credits_account_used'],
                        'credits_account_status'   => $val['credits_account_status'],
                        'credits_account_token'   => $val['credits_account_token'],
                        'credits_account_last_number'   => $val['credits_account_last_number'],
                        'credits_account_approve_status'   => $val['credits_account_approve_status'],
                        'credits_account_reschedule_status'   => $val['credits_account_reschedule_status'],
                        'credits_approve_status'   => $val['credits_approve_status'],
                        'credits_account_temp_installment'   => $val['credits_account_temp_installment'],
                        'auto_debet_credits_account_token'   => $val['auto_debet_credits_account_token'],
                        'data_state'   => $val['data_state'],
                        'created_id'   => $val['created_id'],

                    ]);
            }







            //system login log
            foreach ($request->loginLog as $key => $val) {
                $data = array(
                    'user_id'       => $val['user_id'],
                    'company_id'    => $val['company_id'],
                    'log_time'      => $val['log_time'],
                    'log_status'    => $val['log_status'],
                    'status_upload' => $val['status_upload'],
                    'created_at'    => $val['created_at'],
                    'updated_at'    => $val['updated_at'],
                );
                SystemLoginLog::insert($data);
            }
            // //core member & core member kopkar
            // foreach ($request->member as $key => $val) {
            //     $data_member = CoreMember::where('member_no', $val['member_no'])
            //         ->first();

            //     CoreMember::where('member_no', $val['member_no'])
            //         ->update(['member_account_receivable_amount' => $data_member['member_account_receivable_amount'] + $val['member_account_receivable_amount_temp']]);

            //     $data_member = CoreMemberKopkar::where('member_no', $val['member_no'])
            //         ->first();

            //     CoreMemberKopkar::where('member_no', $val['member_no'])
            //         ->update(['member_account_receivable_amount' => $data_member['member_account_receivable_amount'] + $val['member_account_receivable_amount_temp'], 'member_account_credits_store_debt' => $data_member['member_account_credits_store_debt'] + $val['member_account_receivable_amount_temp']]);
            // }


            //journal voucher
            foreach ($request->sales as $key => $val) {
                $sales_invoice_id   = SalesInvoice::orderBy('created_at', 'DESC')->where('company_id', $val['company_id'])->first();

                 //--------------------------------------- UPDATE SALES INV ITEM -------------------------------//
         
                // sales inv ITEM get TAX amount 
                // $itemsupdSalesInvoiceItem::select('*')
                // ->where('sales_invoice_item.sales_invoice_id', $val['sales_invoice_id'])
                // ->update(['sales_invoice_item.sales_tax_amount' => DB::raw("`sales_invoice_item`.`subtotal_amount_after_discount` - `sales_invoice_item`.`item_unit_price`")]);            //   return json_encode($items);

                //---------------------------------------END UPDATE SALES INV ITEM -------------------------------//

                 //--------------------------------------- GET Amount -------------------------------//
                // ppn Amount
                $ppnAmount = SalesInvoiceItem::select('*', DB::raw("SUM(sales_invoice_item.sales_tax_amount) as total_tax_amount"))
                    ->where('sales_invoice_item.sales_invoice_id', $sales_invoice_id['sales_invoice_id'])
                    ->first();

                //Tipe beras
                $bkp = SalesInvoiceItem::select('sales_invoice_item.*')
                    ->where('sales_invoice_id', $sales_invoice_id['sales_invoice_id'])
                    ->where('bkp', 1)
                    ->first();

                //BKP Amount(beras)
                $total_amount_bkp = SalesInvoiceItem::select('sales_invoice_item.*', DB::raw("SUM(sales_invoice_item.subtotal_amount_after_discount) as total_amount"))
                    ->where('sales_invoice_id', $sales_invoice_id['sales_invoice_id'])
                    ->where('bkp', 1)
                    ->first();

                //harga Beli
                $total_unit_cost = SalesInvoiceItem::select('sales_invoice_item.*', DB::raw("SUM(sales_invoice_item.item_unit_cost) as total_unit_cost"))
                ->where('sales_invoice_id', $sales_invoice_id['sales_invoice_id'])
                ->first();
                //--------------------------------------- END GET Amount -------------------------------//


                if ($bkp && $val['sales_payment_method'] == 1) {
                    $journal_voucher_description  = 'Penjualan Tunai BKP';
                } elseif ($bkp && $val['sales_payment_method'] == 2) {
                    $journal_voucher_description  = 'Penjualan Kredit BKP';
                } elseif ($val['sales_payment_method'] == 6) {
                    $journal_voucher_description  = 'Penjualan Konsinyasi';
                } elseif(empty($bkp) && $val['sales_payment_method'] == 1) {
                    $journal_voucher_description  = 'Penjualan Tunai Non BKP';
                }elseif(empty($bkp) && $val['sales_payment_method'] == 2) {
                    $journal_voucher_description  = 'Penjualan Kredit Non BKP';
                }else{
                    $journal_voucher_description  = 'Penjualan';
                }
                $transaction_module_code    = 'PJL';
                $transaction_module_id      = $this->getTransactionModuleID($transaction_module_code);

                $data_journal = array(
                    'company_id'                    => $val['company_id'],
                    'journal_voucher_status'        => 1,
                    'journal_voucher_description'   => $journal_voucher_description,
                    'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
                    'transaction_module_id'         => $transaction_module_id,
                    'transaction_module_code'       => $transaction_module_code,
                    'journal_voucher_date'          => $val['sales_invoice_date'],
                    'transaction_journal_no'        => $val['sales_invoice_no'],
                    'journal_voucher_period'        => date('Ym', strtotime($val['sales_invoice_date'])),
                    'journal_voucher_segment'       => 2,
                    'updated_id'                    => $val['updated_id'],
                    'created_id'                    => $val['created_id']
                );
                JournalVoucher::create($data_journal);



               


                //jurnal Tunai BKP beras
                if ($bkp && $val['sales_payment_method'] == 1) {
                    $account_setting_name   = 'sales_cash_account';
                    $account_id             = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount   = $val['total_amount'];
                        $credit_amount  = 0;
                    } else {
                        $debit_amount   = 0;
                        $credit_amount  = $val['total_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit);


                    //Penjualan Brg Dagang- BKP Tunai
                    $account_setting_name = 'sales_receivable_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $total_amount_bkp['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $total_amount_bkp['total_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $total_amount_bkp['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit);


                    //ppn bkp Tunai
                    $account_setting_name = 'sales_tax_out_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $ppnAmount['total_tax_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $ppnAmount['total_tax_amount'];
                    }
                    $journal_credit_ppn = array(
                        'company_id'                     => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $ppnAmount['total_tax_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit_ppn);

                    //beban pokok penjualan BKP Tunai
                    $account_setting_name = 'sales_cash_receivable_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount =  $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount =  $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        =>  $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);


                    //Persediaan Barang Dagang
                    $account_setting_name = 'purchase_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);


                    //Kredit
                } else if ($bkp && $val['sales_payment_method'] == 2) {
                    //Piutang Usaha
                    $account_setting_name = 'sales_receivable_account';
                    $account_id             = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount   = $val['total_amount'];
                        $credit_amount  = 0;
                    } else {
                        $debit_amount   = 0;
                        $credit_amount  = $val['total_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit);

                    //Penjualan Brg Dagang- BKP kredit
                    $account_setting_name = 'sales_receivable_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $total_amount_bkp['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $total_amount_bkp['total_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $total_amount_bkp['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit);


                    //ppn bkp Kredit
                    $account_setting_name = 'sales_tax_out_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $ppnAmount['total_tax_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $ppnAmount['total_tax_amount'];
                    }
                    $journal_credit_ppn = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $ppnAmount['total_tax_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit_ppn);


                    //beban pokok penjualan BKP Kredit
                    $account_setting_name = 'sales_cash_receivable_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount =  $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount =  $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        =>  $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);
                    $datacoremember = CoreMember::where('member_id', $val['customer_id'])
                        ->first();
                    CoreMember::where('member_id', $val['customer_id'])
                        ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] + $total_amount_bkp['total_amount'],]);

                    //Persediaan Barang Dagang
                    $account_setting_name = 'purchase_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);


                    //Tunai Non BKP 
                } else if (empty($bkp) && $val['sales_payment_method'] == 1) {
                    $account_setting_name = 'sales_cash_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $val['total_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit);

                    //Penjualan Brg Dagang Non BKP Tunai
                    $account_setting_name = 'sales_cashless_cash_non_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $val['total_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit);

                    //beban pokok penjualan Non BKP Tunai
                    $account_setting_name = 'sales_cash_receivable_non_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount =  $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount =  $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        =>  $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);

                    //Persediaan Barang Dagang
                    $account_setting_name = 'purchase_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);
                } elseif (empty($bkp) && $val['sales_payment_method'] == 2) {
                    //Piutang Usaha
                    $account_setting_name = 'sales_receivable_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $val['total_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit);

                    //Penjualan Brg Dagang Non BKP kredit
                    $account_setting_name = 'sales_cashless_cash_non_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount =  $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount =  $val['total_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        =>  $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit);


                    //ppn Non bkp Kredit
                    // $account_setting_name = 'sales_tax_out_account';
                    // $account_id = $this->getAccountId($account_setting_name);
                    // $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    // $account_default_status = $this->getAccountDefaultStatus($account_id);
                    // $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    // if ($account_setting_status == 0) {
                    //     $debit_amount = $ppnAmount['total_tax_amount'];
                    //     $credit_amount = 0;
                    // } else {
                    //     $debit_amount = 0;
                    //     $credit_amount = $ppnAmount['total_tax_amount'];
                    // }
                    // $journal_credit_ppn = array(
                    //     'company_id'                    => $val['company_id'],
                    //     'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    //     'account_id'                    => $account_id,
                    //     'journal_voucher_amount'        => $ppnAmount['total_tax_amount'],
                    //     'account_id_default_status'     => $account_default_status,
                    //     'account_id_status'             => $account_setting_status,
                    //     'journal_voucher_debit_amount'  => $debit_amount,
                    //     'journal_voucher_credit_amount' => $credit_amount,
                    //     'updated_id'                    => $val['updated_id'],
                    //     'created_id'                    => $val['created_id']
                    // );
                    // JournalVoucherItem::create($journal_credit_ppn);

                    //beban pokok penjualan Non BKP Kredit
                    $account_setting_name = 'sales_cash_receivable_non_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount =   $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount =   $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        =>   $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);
                    $datacoremember = CoreMember::where('member_id', $val['customer_id'])
                        ->first();
                    CoreMember::where('member_id', $val['customer_id'])
                        ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] + $val['total_amount'],]);

                    //Persediaan Barang Dagang
                    $account_setting_name = 'purchase_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);
                } elseif ($val['sales_payment_method'] == 6) {
                    //Piutang Konsinyasi
                    $account_setting_name = 'consignment_debt_receivables';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $val['total_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit);

                    //Hutang konsinyasi 
                    $account_setting_name = 'consignment_cash';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $val['total_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit);


                    //Pendapatan
                    $account_setting_name = 'sales_profit_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $val['total_amount'];
                    }
                    $journal_debit_profit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_profit);
                }else{
                    //Piutang Usaha
                    $account_setting_name = 'sales_receivable_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount =  $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount =  $val['total_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        =>  $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit);
    
                    //Penjualan Brg Dagang Non BKP kredit
                    $account_setting_name = 'sales_receivable_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount =  $val['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount =  $val['total_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        =>  $val['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit);
    
    
                    //ppn Non bkp Kredit
                    $account_setting_name = 'sales_tax_out_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $ppnAmount['total_tax_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $ppnAmount['total_tax_amount'];
                    }
                    $journal_credit_ppn = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $ppnAmount['total_tax_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_credit_ppn);
    
                    //beban pokok penjualan Non BKP Kredit
                    $account_setting_name = 'sales_cash_receivable_bkp_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                    if ($account_setting_status == 0) {
                        $debit_amount =  $total_unit_cost['total_unit_cost'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount =  $total_unit_cost['total_unit_cost'];
                    }
                    $journal_debit_bkp = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        =>  $total_unit_cost['total_unit_cost'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucherItem::create($journal_debit_bkp);
                    $datacoremember = CoreMember::where('member_id', $val['customer_id'])
                    ->first();
                CoreMember::where('member_id', $val['customer_id'])
                    ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] +  $val['total_amount'],]);
                }




                if ($val['data_state'] == 1) {
                    $transaction_module_code = 'HPSPJL';
                    $transaction_module_id = $this->getTransactionModuleID($transaction_module_code);
                    $journal = array(
                        'company_id'                    => $val['company_id'],
                        'journal_voucher_status'        => 1,
                        'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
                        'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
                        'transaction_module_id'         => $transaction_module_id,
                        'transaction_module_code'       => $transaction_module_code,
                        'transaction_journal_no'        => $val['sales_invoice_no'],
                        'journal_voucher_date'          => $val['sales_invoice_date'],
                        'journal_voucher_period'        => date('Ym', strtotime($val['sales_invoice_date'])),
                        'journal_voucher_segment'       => 2,
                        'updated_id'                    => $val['updated_id'],
                        'created_id'                    => $val['created_id']
                    );
                    JournalVoucher::create($journal);
                    if ($val['sales_payment_method'] == 1) {
                        $account_setting_name = 'sales_cash_account';
                        $account_id = $this->getAccountId($account_setting_name);
                        $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                        $account_default_status = $this->getAccountDefaultStatus($account_id);
                        $journal_voucher_id = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                        if ($account_setting_status == 0) {
                            $account_setting_status = 1;
                        } else {
                            $account_setting_status = 0;
                        }
                        if ($account_setting_status == 0) {
                            $debit_amount = $val['total_amount'];
                            $credit_amount = 0;
                        } else {
                            $debit_amount = 0;
                            $credit_amount = $val['total_amount'];
                        }
                        $journal_debit = array(
                            'company_id' => $val['company_id'],
                            'journal_voucher_id' => $journal_voucher_id['journal_voucher_id'],
                            'account_id' => $account_id,
                            'journal_voucher_amount' => $val['total_amount'],
                            'account_id_default_status' => $account_default_status,
                            'account_id_status' => $account_setting_status,
                            'journal_voucher_debit_amount' => $debit_amount,
                            'journal_voucher_credit_amount' => $credit_amount,
                            'updated_id' => $val['updated_id'],
                            'created_id' => $val['created_id']
                        );
                        JournalVoucherItem::create($journal_debit);

                        $account_setting_name = 'sales_account';
                        $account_id = $this->getAccountId($account_setting_name);
                        $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                        $account_default_status = $this->getAccountDefaultStatus($account_id);
                        $journal_voucher_id = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                        if ($account_setting_status == 1) {
                            $account_setting_status = 0;
                        } else {
                            $account_setting_status = 1;
                        }
                        if ($account_setting_status == 0) {
                            $debit_amount = $val['total_amount'];
                            $credit_amount = 0;
                        } else {
                            $debit_amount = 0;
                            $credit_amount = $val['total_amount'];
                        }
                        $journal_credit = array(
                            'company_id' => $val['company_id'],
                            'journal_voucher_id' => $journal_voucher_id['journal_voucher_id'],
                            'account_id' => $account_id,
                            'journal_voucher_amount' => $val['total_amount'],
                            'account_id_default_status' => $account_default_status,
                            'account_id_status' => $account_setting_status,
                            'journal_voucher_debit_amount' => $debit_amount,
                            'journal_voucher_credit_amount' => $credit_amount,
                            'updated_id' => $val['updated_id'],
                            'created_id' => $val['created_id']
                        );
                        JournalVoucherItem::create($journal_credit);
                    } else if ($val['sales_payment_method'] == 2) {
                        $account_setting_name = 'employee_debt';
                        $account_id = $this->getAccountId($account_setting_name);
                        $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                        $account_default_status = $this->getAccountDefaultStatus($account_id);
                        $journal_voucher_id = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                        if ($account_setting_status == 0) {
                            $account_setting_status = 1;
                        } else {
                            $account_setting_status = 0;
                        }
                        if ($account_setting_status == 0) {
                            $debit_amount = $val['total_amount'];
                            $credit_amount = 0;
                        } else {
                            $debit_amount = 0;
                            $credit_amount = $val['total_amount'];
                        }
                        $journal_debit = array(
                            'company_id' => $val['company_id'],
                            'journal_voucher_id' => $journal_voucher_id['journal_voucher_id'],
                            'account_id' => $account_id,
                            'journal_voucher_amount' => $val['total_amount'],
                            'account_id_default_status' => $account_default_status,
                            'account_id_status' => $account_setting_status,
                            'journal_voucher_debit_amount' => $debit_amount,
                            'journal_voucher_credit_amount' => $credit_amount,
                            'updated_id' => $val['updated_id'],
                            'created_id' => $val['created_id']
                        );
                        JournalVoucherItem::create($journal_debit);

                        $account_setting_name = 'employee_debt_receivables';
                        $account_id = $this->getAccountId($account_setting_name);
                        $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                        $account_default_status = $this->getAccountDefaultStatus($account_id);
                        $journal_voucher_id = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                        if ($account_setting_status == 1) {
                            $account_setting_status = 0;
                        } else {
                            $account_setting_status = 1;
                        }
                        if ($account_setting_status == 0) {
                            $debit_amount = $val['total_amount'];
                            $credit_amount = 0;
                        } else {
                            $debit_amount = 0;
                            $credit_amount = $val['total_amount'];
                        }
                        $journal_credit = array(
                            'company_id' => $val['company_id'],
                            'journal_voucher_id' => $journal_voucher_id['journal_voucher_id'],
                            'account_id' => $account_id,
                            'journal_voucher_amount' => $val['total_amount'],
                            'account_id_default_status' => $account_default_status,
                            'account_id_status' => $account_setting_status,
                            'journal_voucher_debit_amount' => $debit_amount,
                            'journal_voucher_credit_amount' => $credit_amount,
                            'updated_id' => $val['updated_id'],
                            'created_id' => $val['created_id']
                        );
                        JournalVoucherItem::create($journal_credit);
                    } else {
                        $account_setting_name   = 'sales_cashless_cash_account';
                        $account_id             = $this->getAccountId($account_setting_name);
                        $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                        $account_default_status = $this->getAccountDefaultStatus($account_id);
                        $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                        if ($account_setting_status == 0) {
                            $account_setting_status = 1;
                        } else {
                            $account_setting_status = 0;
                        }
                        if ($account_setting_status == 0) {
                            $debit_amount = $val['total_amount'];
                            $credit_amount = 0;
                        } else {
                            $debit_amount = 0;
                            $credit_amount = $val['total_amount'];
                        }
                        $journal_debit = array(
                            'company_id' => $val['company_id'],
                            'journal_voucher_id' => $journal_voucher_id['journal_voucher_id'],
                            'account_id' => $account_id,
                            'journal_voucher_amount' => $val['total_amount'],
                            'account_id_default_status' => $account_default_status,
                            'account_id_status' => $account_setting_status,
                            'journal_voucher_debit_amount' => $debit_amount,
                            'journal_voucher_credit_amount' => $credit_amount,
                            'updated_id' => $val['updated_id'],
                            'created_id' => $val['created_id']
                        );
                        JournalVoucherItem::create($journal_debit);

                        $account_setting_name   = 'sales_cashless_account';
                        $account_id             = $this->getAccountId($account_setting_name);
                        $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                        $account_default_status = $this->getAccountDefaultStatus($account_id);
                        $journal_voucher_id     = JournalVoucher::orderBy('journal_voucher_id', 'DESC')->where('company_id', $val['company_id'])->first();
                        if ($account_setting_status == 1) {
                            $account_setting_status = 0;
                        } else {
                            $account_setting_status = 1;
                        }
                        if ($account_setting_status == 0) {
                            $debit_amount = $val['total_amount'];
                            $credit_amount = 0;
                        } else {
                            $debit_amount = 0;
                            $credit_amount = $val['total_amount'];
                        }
                        $journal_credit = array(
                            'company_id'                    => $val['company_id'],
                            'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                            'account_id'                    => $account_id,
                            'journal_voucher_amount'        => $val['total_amount'],
                            'account_id_default_status'     => $account_default_status,
                            'account_id_status'             => $account_setting_status,
                            'journal_voucher_debit_amount'  => $debit_amount,
                            'journal_voucher_credit_amount' => $credit_amount,
                            'updated_id'                    => $val['updated_id'],
                            'created_id'                    => $val['created_id']
                        );
                        JournalVoucherItem::create($journal_credit);
                    }
                }
            }
            //inventory
            foreach ($request->salesItem as $key => $val) {
                $data_packge = InvtItemPackge::where('company_id', $val['company_id'])
                    ->where('item_id', $val['item_id'])
                    ->where('item_unit_id', $val['item_unit_id'])
                    ->where('item_category_id', $val['item_category_id'])
                    ->first();

                $data_stock = InvtItemStock::where('company_id', $val['company_id'])
                    ->where('item_id', $val['item_id'])
                    ->where('item_unit_id', $val['item_unit_id'])
                    ->where('item_category_id', $val['item_category_id'])
                    ->first();

                if (isset($data_stock) && ($val['data_state'] == 0)) {
                    $table                  = InvtItemStock::findOrFail($data_stock['item_stock_id']);
                    $table->last_balance    = (int) $data_stock['last_balance'] - ((int) $val['quantity'] * (int) $data_packge['item_default_quantity']);
                    $table->save();
                }
            }

            // UPDATE NOTE ITEM ID 
            //  foreach ($request->salesItem as $key => $val) {
            //  DB::table('sales_invoice_item as a')
            //  ->join('sales_invoice as c', 'a.sales_invoice_item_id', '=', 'c.sales_invoice_id')
            //  ->update([ 'a.sales_invoice_item' => DB::raw("`c`.`sales_invoice_id`") ]);
            //  }

            // foreach ($request->sales as $key => $val) {
            //     //update limit hutang
            //     $datacoremember = CoreEmployee::select('employee_id','debt_limit','amount_debt','remaining_limit')
            //     ->where('employee_id', $val['employee_id'])
            //     ->first();
            //      if($datacoremember)
            //      {
            //         $debt_limit         = (int)$datacoremember['debt_limit'];
            //         $remaining_limit    = (int)$datacoremember['remaining_limit'];
            //         $amount_debt        = (int)$datacoremember['amount_debt']  +  (int)$val['paid_amount']; 
            //         $remaining_limit    = (int)$datacoremember['debt_limit']  - (int)$val['paid_amount'] - (int)$datacoremember['amount_debt'] ; 

            //         $datacoremember->amount_debt        = $amount_debt ;   
            //         $datacoremember->remaining_limit    = $remaining_limit; 

            //         $datacoremember->save();
            //      }
            // }




            DB::commit();
            return 'true';
        } catch (\Throwable $th) {
            DB::rollback();
            return $th;
        }
    }


    public function getData()
    {


        // CoreMember::select(DB::statement('SET FOREIGN_KEY_CHECKS = 0'))->truncate();
        // foreach ($member_kopkar as $key => $val) {
        //     CoreMember::create([
        //         'member_id' => $val['member_id'],
        //         'member_no' => $val['member_no'],
        //         'member_name' => $val['member_name'],
        //         'division_name' => $val['division_name'],
        //         'member_mandatory_savings' => (int)$val['member_mandatory_savings'],
        //         'member_account_receivable_amount' => (int)$val['member_account_credits_store_debt'],
        //         'member_account_receivable_status' => $val['member_account_receivable_status'],
        //         'member_account_receivable_amount_temp' => (int)$val['member_account_receivable_amount_temp'],
        //         'data_state' => $val['data_state']
        //     ]);
        // }
        // $member     = CoreMember::get();
        $category   = InvtItemCategory::get();
        $unit       = InvtItemUnit::get();
        $barcode    = InvtItemBarcode::get();
        $packge     = InvtItemPackge::get();
        $warehouse  = InvtWarehouse::get();
        $item       = InvtItem::get();
        $stock      = InvtItemStock::get();
        $voucher    = PreferenceVoucher::get();
        $rack       = InvtItemRack::get();

        $data = array(
            // 'member'    => $member,
            'category'  => $category,
            'unit'      => $unit,
            'barcode'   => $barcode,
            'packge'    => $packge,
            'warehouse' => $warehouse,
            'item'      => $item,
            'stock'     => $stock,
            'voucher'   => $voucher,
            'rack'      => $rack,
        );

        return $data;
    }
}
