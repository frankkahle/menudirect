<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("restaurant_site_user", function (Blueprint $table) {
            $table->id();
            $table->foreignId("restaurant_site_id")->constrained()->cascadeOnDelete();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->enum("role", ["owner", "manager"])->default("manager");
            $table->timestamps();
            $table->unique(["restaurant_site_id", "user_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("restaurant_site_user");
    }
};
