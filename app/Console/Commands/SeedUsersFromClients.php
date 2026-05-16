<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedUsersFromClients extends Command
{
    protected $signature = "menudirect:seed-users {--dry-run : Show what would change without writing}";
    protected $description = "Upsert MenuDirect users from SOS Tech clients (those who own restaurants, plus admins)";

    public function handle(): int
    {
        $dryRun = $this->option("dry-run");
        $this->info($dryRun ? "DRY RUN — no writes" : "Seeding users…");

        $ownerIds = DB::connection("menudirect")
            ->table("restaurant_sites")
            ->whereNotNull("client_id")
            ->distinct()
            ->pluck("client_id")
            ->toArray();

        $clients = DB::connection("sostech_clients")
            ->table("clients")
            ->where(function ($q) use ($ownerIds) {
                $q->whereIn("id", $ownerIds)->orWhere("is_admin", 1);
            })
            ->get([
                "id", "name", "email", "password",
                "is_admin", "two_factor_secret", "two_factor_recovery_codes",
                "two_factor_confirmed_at", "created_at", "updated_at",
            ]);

        $created = $updated = $unchanged = 0;

        foreach ($clients as $c) {
            $existing = DB::table("users")->where("id", $c->id)->first();

            $payload = [
                "id" => $c->id,
                "name" => $c->name,
                "email" => $c->email,
                "email_verified_at" => $c->created_at, // sos_portal.clients has no verification column; assume verified since they're in production
                "password" => $c->password,
                "is_admin" => (bool) $c->is_admin,
                "two_factor_secret" => $c->two_factor_secret,
                "two_factor_recovery_codes" => $c->two_factor_recovery_codes,
                "two_factor_confirmed_at" => $c->two_factor_confirmed_at,
                "created_at" => $c->created_at,
                "updated_at" => $c->updated_at,
            ];

            if (!$existing) {
                if (!$dryRun) DB::table("users")->insert($payload);
                $created++;
                $this->line("  + {$c->email} (id={$c->id})");
            } elseif ($this->differs((array) $existing, $payload)) {
                if (!$dryRun) DB::table("users")->where("id", $c->id)->update($payload);
                $updated++;
                $this->line("  ~ {$c->email} (id={$c->id})");
            } else {
                $unchanged++;
            }
        }

        $this->info("");
        $this->info("Summary: created={$created}, updated={$updated}, unchanged={$unchanged}, total=" . count($clients));

        return self::SUCCESS;
    }

    protected function differs(array $existing, array $new): bool
    {
        foreach (["name", "email", "password", "is_admin", "two_factor_secret", "two_factor_recovery_codes", "two_factor_confirmed_at"] as $k) {
            if (($existing[$k] ?? null) != ($new[$k] ?? null)) return true;
        }
        return false;
    }
}
