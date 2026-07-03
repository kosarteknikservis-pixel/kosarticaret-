<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasKaysu = DB::table('brands')->where('slug', 'kaysu')->exists();
        $hasKaysuPompa = DB::table('brands')->where('slug', 'kaysu-pompa')->exists();

        if ($hasKaysuPompa && ! $hasKaysu) {
            DB::table('brands')->where('slug', 'kaysu-pompa')->update(['slug' => 'kaysu']);
        }
    }

    public function down(): void
    {
        $hasKaysu = DB::table('brands')->where('slug', 'kaysu')->exists();
        $hasKaysuPompa = DB::table('brands')->where('slug', 'kaysu-pompa')->exists();

        if ($hasKaysu && ! $hasKaysuPompa) {
            DB::table('brands')->where('slug', 'kaysu')->update(['slug' => 'kaysu-pompa']);
        }
    }
};
