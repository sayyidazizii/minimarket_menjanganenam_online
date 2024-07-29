<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInvtItemItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invt_item', function (Blueprint $table) {
            $table->string('tax_ppn_percentage_purchase',100)->nullable();
            $table->string('tax_ppn_percentage_sales',100)->nullable();
            $table->string('tax_ppn_amount_purchase',100)->nullable();
            $table->string('tax_ppn_amount_sales',100)->nullable();
            $table->string('discount_ppn_percentage_purchase',100)->nullable();
            $table->string('discount_ppn_percentage_sales',100)->nullable();
            $table->string('discount_ppn_amount_purchase',100)->nullable();
            $table->string('discount_ppn_amount_sales',100)->nullable();
            $table->string('profit',100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invt_item', function (Blueprint $table) {
            //
        });
    }
}
