<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('company_settings')) {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('merchant_id')->nullable();
            $table->integer('branch_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('foregin_id')->nullable();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->json('properties')->nullable();
            $table->integer('created_id')->nullable();
            $table->integer('updated_id')->nullable();
            $table->integer('deleted_id')->nullable();
            $table->string('type')->nullable();
            $table->uuid('uuid')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
        Schema::dropIfExists('company_settings');
    }
}
