<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemCostUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('item_cost_updates')) {
        Schema::create('item_cost_updates', function (Blueprint $table) {
            $table->id();
            $table->integer('item_id')->nullable();
            $table->integer('item_packge_id')->nullable();
        
            $table->integer('margin_percentage_old')->nullable();
            $table->integer('discount_percentage_old')->nullable();
            $table->integer('ppn_percentage_old')->nullable();
            $table->string('discount_amount_old',100)->nullable();
            $table->string('ppn_amount_old',100)->nullable();
            $table->string('item_cost_old',100)->nullable();
            $table->string('item_price_old',100)->nullable();
            $table->string('profit_old',100)->nullable();

            $table->integer('margin_percentage_new')->nullable();
            $table->integer('discount_percentage_new')->nullable();
            $table->integer('ppn_percentage_new')->nullable();
            $table->string('discount_amount_new',100)->nullable();
            $table->string('ppn_amount_new',100)->nullable();
            $table->string('item_cost_new',100)->nullable();
            $table->string('item_price_new',100)->nullable();
            $table->string('profit_new',100)->nullable();

            $table->text('remark')->nullable();
            $table->char('token',36)->nullable();

            $table->integer('purchase_quanity')->nullable();
            $table->integer('purchase_id')->nullable();
            $table->date('purchase_date')->nullable();
            $table->timestamp('change_date')->nullable();
            $table->integer('created_id')->nullable();
            $table->integer('updated_id')->nullable();
            $table->integer('deleted_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
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
        Schema::dropIfExists('item_cost_updates');
    }
}
