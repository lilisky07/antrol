<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('referensi_mobilejkn_bpjs') && Schema::hasColumn('referensi_mobilejkn_bpjs', 'status')) {
            // Use raw ALTER TABLE to avoid doctrine type mapping issues
            DB::statement("ALTER TABLE referensi_mobilejkn_bpjs MODIFY COLUMN status ENUM('Belum','Checkin','Batal','Gagal') NOT NULL DEFAULT 'Belum'");
        }
    }

    public function down()
    {
        if (Schema::hasTable('referensi_mobilejkn_bpjs') && Schema::hasColumn('referensi_mobilejkn_bpjs', 'status')) {
            DB::statement("ALTER TABLE referensi_mobilejkn_bpjs MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Belum'");
        }
    }
};
