<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE dynamic_pages MODIFY banner_image_url VARCHAR(255) NULL');
            DB::statement('ALTER TABLE dynamic_pages MODIFY appbar_image_url VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE dynamic_pages MODIFY banner_image_url VARCHAR(255) NOT NULL DEFAULT ''");
            DB::statement("ALTER TABLE dynamic_pages MODIFY appbar_image_url VARCHAR(255) NOT NULL DEFAULT ''");
        }
    }
};
