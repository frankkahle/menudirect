<?php

namespace App\Http\Controllers\Api\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManagedOwnerResource;
use App\Http\Resources\ManagedSiteResource;
use App\Services\SiteProvisioningService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private SiteProvisioningService $svc) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner.email' => ['required', 'email', 'max:255'],
            'owner.name' => ['required', 'string', 'max:255'],
            'site.business_name' => ['required', 'string', 'max:255'],
            'site.slug' => ['sometimes', 'string', 'regex:/^[a-z0-9\-]+$/', 'max:255'],
            'site.template' => ['sometimes', 'string', 'max:60'],
            'plan_id' => ['required', 'integer', 'exists:menudirect.restaurant_plans,id'],
            'status' => ['sometimes', 'in:demo,active,suspended'],
            'send_welcome_email' => ['sometimes', 'boolean'],
        ]);

        $r = $this->svc->provisionCustomer(
            $data['owner'],
            $data['site'],
            (int) $data['plan_id'],
            $data['status'] ?? null,
            (bool) ($data['send_welcome_email'] ?? false)
        );

        return response()->json([
            'owner' => new ManagedOwnerResource($r['owner']),
            'set_password_url' => $r['set_password_url'],
            'site' => new ManagedSiteResource($r['site']),
        ], 201);
    }
}
