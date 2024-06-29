<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInvtItemPackgeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('invt_item_packge', 'item_unit_ppn')) {
            Schema::table('invt_item_packge', function($table) {
                $table->string('item_unit_ppn',100)->nullable();
                $table->string('item_unit_cost_after_ppn',100)->nullable();
                $table->string('item_unit_discount',100)->nullable();
                $table->string('item_unit_cost_final',100)->nullable();
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
        //
    }
}
