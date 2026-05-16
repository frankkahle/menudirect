<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->boolean("is_admin")->default(false)->after("password");
            $table->text("two_factor_secret")->nullable()->after("is_admin");
            $table->text("two_factor_recovery_codes")->nullable()->after("two_factor_secret");
            $table->timestamp("two_factor_confirmed_at")->nullable()->after("two_factor_recovery_codes");
            $table->index("email", "users_email_index");
        });
    }

    public function down(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->dropIndex("users_email_index");
            $table->dropColumn(["is_admin", "two_factor_secret", "two_factor_recovery_codes", "two_factor_confirmed_at"]);
        });
    }
};
