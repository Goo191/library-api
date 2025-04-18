<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('qr_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('qr_logs', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('qr_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('qr_logs', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
}; 