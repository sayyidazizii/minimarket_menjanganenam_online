<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInvtItemPackge1Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invt_item_packge', function (Blueprint $table) {
            if (Schema::hasColumn('invt_item_packge', 'item_unit_ppn')) {
                $table->dropColumn('item_unit_ppn');
                $table->dropColumn('item_unit_cost_after_ppn');
                $table->dropColumn('item_unit_discount');
            }
            $table->string('tax_ppn_percentage_purchase',100)->nullable();
            $table->string('tax_ppn_percentage_sales',100)->nullable();
            $table->string('tax_ppn_amount_purchase',100)->nullable();
            $table->string('tax_ppn_amount_sales',100)->nullable();
            $table->string('discount_percentage_purchase',100)->nullable();
            $table->string('discount_percentage_sales',100)->nullable();
            $table->string('discount_amount_purchase',100)->nullable();
            $table->string('discount_amount_sales',100)->nullable();
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
        Schema::table('invt_item_packge', function (Blueprint $table) {
            //
        });
    }
}
