<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection("menudirect")->table("restaurant_sites", function (Blueprint $table) {
            $table->timestamp("archived_at")->nullable()->after("updated_at")->index();
        });
    }

    public function down(): void
    {
        Schema::connection("menudirect")->table("restaurant_sites", function (Blueprint $table) {
            $table->dropColumn("archived_at");
        });
    }
};
