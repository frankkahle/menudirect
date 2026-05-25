<?php

namespace App\Http\Controllers\Api\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManagedOwnerResource;
use App\Services\SiteProvisioningService;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function __construct(private SiteProvisioningService $svc) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'send_welcome_email' => ['sometimes', 'boolean'],
            'reissue_invite' => ['sometimes', 'boolean'],
        ]);

        $r = $this->svc->createOwner($data['email'], $data['name'], (bool) ($data['reissue_invite'] ?? false));

        if (! $r['already_existed']) {
            app(\App\Services\Audit\AuditService::class)->log('manage.owner.created', [
                'resource_type' => 'user', 'resource_id' => $r['owner']->id,
                'description' => 'Owner created via management API',
            ]);
        }

        return response()->json([
            'owner' => new ManagedOwnerResource($r['owner']),
            'set_password_url' => $r['set_password_url'],
            'set_password_expires_at' => $r['set_password_expires_at'],
            'already_existed' => $r['already_existed'],
        ], $r['already_existed'] ? 200 : 201);
    }
}
