<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAcctJournalVoucherTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('acct_journal_voucher', 'transaction_journal_id')) {
            Schema::table('acct_journal_voucher', function (Blueprint $table) {
                $table->unsignedBigInteger('transaction_journal_id')->nullable();
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
        Schema::table('acct_journal_voucher', function (Blueprint $table) {
            //
        });
    }
}
