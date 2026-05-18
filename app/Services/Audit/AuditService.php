<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an audit event
     *
     * @param string $action The action being performed (e.g., 'domain.lock', 'transfer.start')
     * @param array $options Additional options:
     *   - client_id: Client who performed the action
     *   - user_id: Admin user who performed the action
     *   - domain_id: Domain affected (if applicable)
     *   - resource_type: Type of resource (domain, contact, nameserver, etc.)
     *   - resource_id: ID of the resource
     *   - old_values: State before the action
     *   - new_values: State after the action
     *   - description: Human-readable description
     *   - request: The HTTP request object (optional, will be inferred)
     */
    public function log(string $action, array $options = []): AuditLog
    {
        $request = $options['request'] ?? request();

        // Automatically detect client/user if not provided
        $clientId = $options['client_id'] ?? null;
        $userId = $options['user_id'] ?? null;

        if (!$clientId && !$userId && Auth::check()) {
            $authUser = Auth::user();
            if ($authUser instanceof Client) {
                $clientId = $authUser->id;
            } elseif ($authUser instanceof User) {
                $userId = $authUser->id;
            }
        }

        return AuditLog::create([
            'client_id' => $clientId,
            'user_id' => $userId,
            'domain_id' => $options['domain_id'] ?? null,
            'action' => $action,
            'resource_type' => $options['resource_type'] ?? null,
            'resource_id' => $options['resource_id'] ?? null,
            'ip_address' => $request ? $request->ip() : '0.0.0.0',
            'user_agent' => $request ? $request->userAgent() : null,
            'old_values' => $options['old_values'] ?? null,
            'new_values' => $options['new_values'] ?? null,
            'description' => $options['description'] ?? null,
        ]);
    }

    /**
     * Log domain lock action
     */
    public function logDomainLock(Domain $domain, ?Request $request = null): AuditLog
    {
        return $this->log('domain.lock', [
            'domain_id' => $domain->id,
            'resource_type' => 'domain',
            'resource_id' => $domain->id,
            'description' => "Domain {$domain->name} was locked",
            'new_values' => ['is_locked' => true],
            'request' => $request,
        ]);
    }

    /**
     * Log domain unlock action
     */
    public function logDomainUnlock(Domain $domain, ?Request $request = null): AuditLog
    {
        return $this->log('domain.unlock', [
            'domain_id' => $domain->id,
            'resource_type' => 'domain',
            'resource_id' => $domain->id,
            'description' => "Domain {$domain->name} was unlocked",
            'new_values' => ['is_locked' => false],
            'request' => $request,
        ]);
    }

    /**
     * Log nameserver update
     */
    public function logNameserversUpdate(Domain $domain, array $oldNs, array $newNs, ?Request $request = null): AuditLog
    {
        return $this->log('domain.nameservers_update', [
            'domain_id' => $domain->id,
            'resource_type' => 'domain',
            'resource_id' => $domain->id,
            'description' => "Nameservers updated for {$domain->name}",
            'old_values' => ['nameservers' => $oldNs],
            'new_values' => ['nameservers' => $newNs],
            'request' => $request,
        ]);
    }

    /**
     * Log EPP code access
     */
    public function logEppAccess(Domain $domain, ?Request $request = null): AuditLog
    {
        return $this->log('domain.epp_access', [
            'domain_id' => $domain->id,
            'resource_type' => 'domain',
            'resource_id' => $domain->id,
            'description' => "EPP/Auth code accessed for {$domain->name}",
            'request' => $request,
        ]);
    }

    /**
     * Log domain renewal
     */
    public function logDomainRenewal(Domain $domain, int $years, ?Request $request = null): AuditLog
    {
        return $this->log('domain.renew', [
            'domain_id' => $domain->id,
            'resource_type' => 'domain',
            'resource_id' => $domain->id,
            'description' => "Domain {$domain->name} renewed for {$years} year(s)",
            'new_values' => ['years' => $years],
            'request' => $request,
        ]);
    }

    /**
     * Log transfer initiation
     */
    public function logTransferStart(string $domainName, ?int $domainId = null, ?Request $request = null): AuditLog
    {
        return $this->log('transfer.start', [
            'domain_id' => $domainId,
            'resource_type' => 'transfer',
            'description' => "Transfer initiated for {$domainName}",
            'request' => $request,
        ]);
    }

    /**
     * Log transfer cancellation
     */
    public function logTransferCancel(string $domainName, ?int $domainId = null, ?Request $request = null): AuditLog
    {
        return $this->log('transfer.cancel', [
            'domain_id' => $domainId,
            'resource_type' => 'transfer',
            'description' => "Transfer cancelled for {$domainName}",
            'request' => $request,
        ]);
    }

    /**
     * Log contact update
     */
    public function logContactUpdate(Domain $domain, array $oldContact, array $newContact, ?Request $request = null): AuditLog
    {
        return $this->log('domain.contacts_update', [
            'domain_id' => $domain->id,
            'resource_type' => 'domain',
            'resource_id' => $domain->id,
            'description' => "Contact information updated for {$domain->name}",
            'old_values' => $oldContact,
            'new_values' => $newContact,
            'request' => $request,
        ]);
    }

    /**
     * Log 2FA enabled
     */
    public function log2faEnabled(?Request $request = null): AuditLog
    {
        return $this->log('2fa.enabled', [
            'description' => "Two-factor authentication enabled",
            'request' => $request,
        ]);
    }

    /**
     * Log 2FA disabled
     */
    public function log2faDisabled(?Request $request = null): AuditLog
    {
        return $this->log('2fa.disabled', [
            'description' => "Two-factor authentication disabled",
            'request' => $request,
        ]);
    }

    /**
     * Log password change
     */
    public function logPasswordChange(?Request $request = null): AuditLog
    {
        return $this->log('auth.password_change', [
            'description' => "Password changed",
            'request' => $request,
        ]);
    }

    /**
     * Log auto-renew toggle
     */
    public function logAutoRenewToggle(Domain $domain, bool $oldValue, bool $newValue, ?Request $request = null): AuditLog
    {
        $status = $newValue ? 'enabled' : 'disabled';

        return $this->log('domain.auto_renew_toggle', [
            'domain_id' => $domain->id,
            'resource_type' => 'domain',
            'resource_id' => $domain->id,
            'description' => "Auto-renew {$status} for {$domain->name}",
            'old_values' => ['auto_renew' => $oldValue],
            'new_values' => ['auto_renew' => $newValue],
            'request' => $request,
        ]);
    }

    /**
     * Log privacy protection toggle
     */
    public function logPrivacyToggle(Domain $domain, bool $oldValue, bool $newValue, ?Request $request = null): AuditLog
    {
        $status = $newValue ? 'enabled' : 'disabled';

        return $this->log('domain.privacy_toggle', [
            'domain_id' => $domain->id,
            'resource_type' => 'domain',
            'resource_id' => $domain->id,
            'description' => "Privacy protection {$status} for {$domain->name}",
            'old_values' => ['privacy_enabled' => $oldValue],
            'new_values' => ['privacy_enabled' => $newValue],
            'request' => $request,
        ]);
    }

    /**
     * Log successful login
     */
    public function logLogin(Client $client, ?Request $request = null): AuditLog
    {
        $this->writeAuthLog('login_success', $client->email, $request);

        return $this->log('auth.login', [
            'client_id' => $client->id,
            'description' => "User logged in: {$client->email}",
            'request' => $request,
        ]);
    }

    /**
     * Log failed login attempt
     */
    public function logLoginFailed(string $email, ?Request $request = null): AuditLog
    {
        $this->writeAuthLog('login_failed', $email, $request);

        return $this->log('auth.login_failed', [
            'description' => "Failed login attempt for: {$email}",
            'new_values' => ['attempted_email' => $email],
            'request' => $request,
        ]);
    }

    /**
     * Log logout
     */
    public function logLogout(?Client $client = null, ?Request $request = null): AuditLog
    {
        $email = $client?->email ?? 'unknown';
        $this->writeAuthLog('logout', $email, $request);

        return $this->log('auth.logout', [
            'client_id' => $client?->id,
            'description' => "User logged out: {$email}",
            'request' => $request,
        ]);
    }

    /**
     * Log 2FA challenge success
     */
    public function log2faSuccess(Client $client, ?Request $request = null): AuditLog
    {
        $this->writeAuthLog('2fa_success', $client->email, $request);

        return $this->log('auth.2fa_success', [
            'client_id' => $client->id,
            'description' => "2FA verification successful for: {$client->email}",
            'request' => $request,
        ]);
    }

    /**
     * Log 2FA challenge failure
     */
    public function log2faFailed(string $email, ?Request $request = null): AuditLog
    {
        $this->writeAuthLog('2fa_failed', $email, $request);

        return $this->log('auth.2fa_failed', [
            'description' => "2FA verification failed for: {$email}",
            'new_values' => ['attempted_email' => $email],
            'request' => $request,
        ]);
    }

    /**
     * Write to dedicated auth log file
     */
    protected function writeAuthLog(string $event, string $email, ?Request $request = null): void
    {
        $ip = $request ? $request->ip() : '0.0.0.0';
        $userAgent = $request ? ($request->userAgent() ?? 'unknown') : 'unknown';

        $logLine = sprintf(
            '[%s] %s | email=%s | ip=%s | ua=%s',
            now()->toIso8601String(),
            strtoupper($event),
            $email,
            $ip,
            $userAgent
        );

        \Illuminate\Support\Facades\Log::channel('auth')->info($logLine);
    }
}
