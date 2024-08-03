<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSystemUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('system_user', 'branch_id')) {
            Schema::table('system_user', function (Blueprint $table) {
                $table->string('branch_id',100)->nullable();
                $table->uuid('uuid')->nullable();
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
        Schema::table('system_user', function (Blueprint $table) {
            //
        });
    }
}
