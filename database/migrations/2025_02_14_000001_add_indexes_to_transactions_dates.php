<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['check_in', 'check_out'], 'transactions_check_in_check_out_index');
            $table->index(['room_id', 'check_in'], 'transactions_room_check_in_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_room_check_in_index');
            $table->dropIndex('transactions_check_in_check_out_index');
        });
    }
};
