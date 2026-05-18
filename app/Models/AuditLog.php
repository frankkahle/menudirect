<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'domain_id',
        'action',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the actor (client or user) who performed the action
     */
    public function getActorAttribute()
    {
        if ($this->user) {
            return $this->user->name . ' (Admin)';
        }
        if ($this->client) {
            return $this->client->name;
        }
        return 'System';
    }

    /**
     * Get human-readable action name
     */
    public function getActionNameAttribute(): string
    {
        return match($this->action) {
            'domain.lock' => 'Domain Locked',
            'domain.unlock' => 'Domain Unlocked',
            'domain.nameservers_update' => 'Nameservers Updated',
            'domain.contacts_update' => 'Contacts Updated',
            'domain.epp_access' => 'EPP Code Accessed',
            'domain.renew' => 'Domain Renewed',
            'transfer.start' => 'Transfer Initiated',
            'transfer.cancel' => 'Transfer Cancelled',
            'transfer.approve' => 'Transfer Approved',
            'transfer.reject' => 'Transfer Rejected',
            'contact.create' => 'Contact Created',
            'contact.update' => 'Contact Updated',
            'contact.delete' => 'Contact Deleted',
            'nameserver.create' => 'Nameserver Created',
            'nameserver.update' => 'Nameserver Updated',
            'nameserver.delete' => 'Nameserver Deleted',
            'auth.login' => 'User Logged In',
            'auth.logout' => 'User Logged Out',
            'auth.password_change' => 'Password Changed',
            '2fa.enabled' => 'Two-Factor Authentication Enabled',
            '2fa.disabled' => 'Two-Factor Authentication Disabled',
            default => ucwords(str_replace(['.', '_'], ' ', $this->action)),
        };
    }
}
