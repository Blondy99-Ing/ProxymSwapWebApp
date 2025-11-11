<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('swaps', function (Blueprint $table) {
            $table->unsignedBigInteger('employe_id')->nullable()->after('agent_user_id');
            $table->foreign('employe_id')->references('id')->on('employes')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('swaps', function (Blueprint $table) {
            $table->dropForeign(['employe_id']);
            $table->dropColumn('employe_id');
        });
    }
};
