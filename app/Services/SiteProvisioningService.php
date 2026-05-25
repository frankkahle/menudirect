<?php

namespace App\Services;

use App\Exceptions\ProvisioningConflictException;
use App\Models\RestaurantPlan;
use App\Models\RestaurantSite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * The single place that applies operational state pushed by the SOS portal:
 * owners, sites, plans, status. No billing — that stays in the SOS portal.
 */
class SiteProvisioningService
{
    /**
     * Create (or return existing) owner login. New owners get a one-time
     * set-password link via the existing password-reset flow; no usable
     * password is ever set or returned.
     *
     * @return array{owner: User, set_password_url: ?string, set_password_expires_at: ?string, already_existed: bool}
     */
    public function createOwner(string $email, string $name, bool $reissueInvite = false): array
    {
        $existing = User::where('email', $email)->first();
        if ($existing) {
            return [
                'owner' => $existing,
                'set_password_url' => $reissueInvite ? $this->inviteUrl($existing) : null,
                'set_password_expires_at' => null,
                'already_existed' => true,
            ];
        }

        $owner = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(Str::random(40)),
        ]);

        return [
            'owner' => $owner,
            'set_password_url' => $this->inviteUrl($owner),
            'set_password_expires_at' => now()->addHour()->toIso8601String(),
            'already_existed' => false,
        ];
    }

    public function provisionSite(array $data): RestaurantSite
    {
        $plan = RestaurantPlan::findOrFail($data['plan_id']);
        $owner = $this->resolveOwner($data);

        $slug = $data['slug'] ?? Str::slug($data['business_name']);
        if (RestaurantSite::withoutGlobalScope('notArchived')->where('slug', $slug)->exists()) {
            throw new ProvisioningConflictException("Slug '{$slug}' is already taken.");
        }

        return RestaurantSite::create([
            'client_id' => $owner->id,
            'restaurant_plan_id' => $plan->id,
            'plan' => $this->mapPlanToType($plan->slug),
            'slug' => $slug,
            'business_name' => $data['business_name'],
            'status' => $data['status'] ?? RestaurantSite::STATUS_ACTIVE,
            'ordering_enabled' => (bool) $plan->online_ordering,
        ]);
    }

    public function changePlan(RestaurantSite $site, int $planId): RestaurantSite
    {
        $plan = RestaurantPlan::findOrFail($planId);
        $site->update([
            'restaurant_plan_id' => $plan->id,
            'plan' => $this->mapPlanToType($plan->slug),
            'ordering_enabled' => (bool) $plan->online_ordering,
        ]);

        return $site->refresh();
    }

    public function setStatus(RestaurantSite $site, string $status): RestaurantSite
    {
        // archived_at is not mass-assignable, so set it directly. Don't re-query an
        // archived row — the notArchived global scope would hide it.
        if ($status === 'archived') {
            $site->archived_at = now();
            $site->save();
            return $site;
        }

        $site->archived_at = null;
        $site->status = $status;
        $site->save();
        return $site;
    }

    /**
     * Atomic "add a customer": create-or-get owner, provision site, assign plan.
     *
     * @return array{owner: User, set_password_url: ?string, site: RestaurantSite}
     */
    public function provisionCustomer(array $owner, array $site, int $planId, ?string $status = null, bool $sendWelcome = false): array
    {
        return DB::transaction(function () use ($owner, $site, $planId, $status) {
            $ownerResult = $this->createOwner($owner['email'], $owner['name'], reissueInvite: true);
            $newSite = $this->provisionSite([
                'business_name' => $site['business_name'],
                'slug' => $site['slug'] ?? null,
                'template' => $site['template'] ?? null,
                'plan_id' => $planId,
                'owner_id' => $ownerResult['owner']->id,
                'status' => $status ?? RestaurantSite::STATUS_ACTIVE,
            ]);

            return [
                'owner' => $ownerResult['owner'],
                'set_password_url' => $ownerResult['set_password_url'],
                'site' => $newSite,
            ];
        });
    }

    protected function resolveOwner(array $data): User
    {
        if (! empty($data['owner_id'])) {
            return User::findOrFail($data['owner_id']);
        }

        return User::where('email', $data['owner_email'] ?? '')->firstOrFail();
    }

    protected function mapPlanToType(string $slug): string
    {
        return match ($slug) {
            'basic' => RestaurantSite::PLAN_BASIC,
            'sitefresh' => RestaurantSite::PLAN_SELFSERVICE,
            'sitefresh-pro' => RestaurantSite::PLAN_PREMIUM,
            'menudirect-max' => RestaurantSite::PLAN_MAX,
            default => RestaurantSite::PLAN_BASIC,
        };
    }

    private function inviteUrl(User $owner): string
    {
        $token = Password::createToken($owner);

        return route('password.reset', ['token' => $token]) . '?email=' . urlencode($owner->email);
    }
}
