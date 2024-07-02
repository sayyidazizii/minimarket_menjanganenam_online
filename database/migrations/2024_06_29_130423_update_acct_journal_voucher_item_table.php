<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAcctJournalVoucherItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('acct_journal_voucher_item', 'account_setting_name')) {
            Schema::table('acct_journal_voucher_item', function (Blueprint $table) {
                $table->string('account_setting_name',100)->nullable();
                $table->unsignedBigInteger('account_setting_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('acct_journal_voucher_item', function (Blueprint $table) {
            //
        });
    }
}
