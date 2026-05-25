<?php

namespace App\Http\Controllers\Api\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManagedSiteResource;
use App\Models\RestaurantSite;
use App\Services\SiteProvisioningService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(private SiteProvisioningService $svc) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/'],
            'template' => ['sometimes', 'string', 'max:60'],
            'plan_id' => ['required', 'integer', 'exists:menudirect.restaurant_plans,id'],
            'owner_id' => ['required_without:owner_email', 'integer'],
            'owner_email' => ['required_without:owner_id', 'email'],
            'status' => ['sometimes', 'in:demo,active,suspended'],
        ]);

        $site = $this->svc->provisionSite($data);

        app(\App\Services\Audit\AuditService::class)->log('manage.site.provisioned', [
            'resource_type' => 'restaurant_site', 'resource_id' => $site->id,
            'description' => "Site '{$site->slug}' provisioned via management API",
        ]);

        return response()->json(['site' => new ManagedSiteResource($site)], 201);
    }

    public function changePlan(Request $request, RestaurantSite $site)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:menudirect.restaurant_plans,id'],
        ]);

        $site = $this->svc->changePlan($site, (int) $data['plan_id']);

        app(\App\Services\Audit\AuditService::class)->log('manage.site.plan_changed', [
            'resource_type' => 'restaurant_site', 'resource_id' => $site->id,
            'description' => "Site '{$site->slug}' plan changed to {$site->plan} via management API",
        ]);

        return response()->json(['site' => new ManagedSiteResource($site)]);
    }

    public function setStatus(Request $request, RestaurantSite $site)
    {
        $data = $request->validate([
            'status' => ['required', 'in:demo,active,suspended,archived'],
        ]);

        $site = $this->svc->setStatus($site, $data['status']);

        app(\App\Services\Audit\AuditService::class)->log('manage.site.status_changed', [
            'resource_type' => 'restaurant_site', 'resource_id' => $site->id,
            'description' => "Site '{$site->slug}' status set to {$data['status']} via management API",
        ]);

        return response()->json(['site' => new ManagedSiteResource($site)]);
    }
}
