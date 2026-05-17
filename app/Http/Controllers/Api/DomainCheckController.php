<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomainPricing;
use App\Services\Openprovider\Commands\CheckDomainCommand;
use Illuminate\Http\Request;

class DomainCheckController extends Controller
{
    public function __construct(
        private CheckDomainCommand $lookup
    ) {}

    public function check(Request $request)
    {
        $request->validate([
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = strtolower(trim($request->input('domain')));

        // Add .ca if no TLD provided
        if (!str_contains($domain, '.')) {
            $domain .= '.ca';
        }

        $results = [];

        try {
            // Check the searched domain
            $searchedResult = $this->lookup->execute($domain);
            $searchedPricing = $this->getPricing($domain);

            $results[] = [
                'domain' => $domain,
                'available' => $searchedResult['available'] ?? null,
                'premium' => $searchedResult['premium'] ?? false,
                'pricing' => $searchedPricing,
            ];

            // If main domain available, also check alternatives
            $parts = explode('.', $domain, 2);
            $baseName = $parts[0];
            $currentTld = '.' . ($parts[1] ?? 'ca');

            // Check a few popular alternatives
            $altTlds = ['.ca', '.com', '.net'];
            $alternativeDomains = [];

            foreach ($altTlds as $tld) {
                if ($currentTld !== $tld) {
                    $alternativeDomains[] = $baseName . $tld;
                }
            }

            if (!empty($alternativeDomains)) {
                $altResults = $this->lookup->checkMultiple($alternativeDomains);

                foreach ($alternativeDomains as $altDomain) {
                    $result = $altResults[$altDomain] ?? null;
                    if ($result) {
                        $pricing = $this->getPricing($altDomain);
                        $results[] = [
                            'domain' => $altDomain,
                            'available' => $result['available'] ?? null,
                            'premium' => $result['premium'] ?? false,
                            'pricing' => $pricing,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to check domain availability',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'results' => $results,
        ]);
    }

    private function getPricing(string $domain): ?array
    {
        try {
            $parts = explode('.', $domain);
            if (count($parts) < 2) {
                return null;
            }

            // Try compound TLD first (e.g., "co.uk")
            $pricing = null;
            if (count($parts) >= 3) {
                $compoundTld = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
                $pricing = DomainPricing::forTld($compoundTld);
            }

            // Fall back to simple TLD
            if (!$pricing) {
                $tld = $parts[count($parts) - 1];
                $pricing = DomainPricing::forTld($tld);
            }

            if (!$pricing) {
                return null;
            }

            return [
                'retail' => (float) $pricing->price_register,
                'renew' => (float) $pricing->price_renew,
                'currency' => 'CAD',
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
