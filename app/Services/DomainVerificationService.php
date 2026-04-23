<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\Http;

class DomainVerificationService
{
    /**
     * Verify that a school's custom domain is correctly configured.
     *
     * Checks:
     * 1. DNS resolves (A/CNAME record points somewhere)
     * 2. The domain actually serves our app (HTTP check)
     *
     * @return array{verified: bool, dns_ok: bool, http_ok: bool, dns_ip: ?string, message: string}
     */
    public function verify(School $school): array
    {
        if (! $school->custom_domain) {
            return [
                'verified' => false,
                'dns_ok' => false,
                'http_ok' => false,
                'dns_ip' => null,
                'message' => 'No custom domain configured.',
            ];
        }

        $domain = $school->custom_domain;
        $serverIp = config('services.server_ip', env('SERVER_IP', ''));

        // Step 1: DNS check — does the domain resolve?
        $dnsIp = $this->resolveDns($domain);
        $dnsOk = $dnsIp !== null;

        // If we have a server IP, check if it matches
        $dnsPointsToUs = $serverIp && $dnsIp === $serverIp;

        // Step 2: HTTP check — does the domain serve our app?
        $httpOk = false;
        if ($dnsOk) {
            $httpOk = $this->checkHttp($domain);
        }

        $verified = $dnsOk && $httpOk;

        // Update the school record
        if ($verified && ! $school->domain_verified_at) {
            $school->update(['domain_verified_at' => now()]);
        } elseif (! $verified && $school->domain_verified_at) {
            $school->update(['domain_verified_at' => null]);
        }

        // Build a human-friendly message
        $message = $this->buildMessage($dnsOk, $dnsIp, $dnsPointsToUs, $httpOk, $serverIp);

        return [
            'verified' => $verified,
            'dns_ok' => $dnsOk,
            'http_ok' => $httpOk,
            'dns_ip' => $dnsIp,
            'message' => $message,
        ];
    }

    /**
     * Resolve the domain to an IP address via DNS.
     */
    private function resolveDns(string $domain): ?string
    {
        // Try A record first
        $ip = gethostbyname($domain);

        // gethostbyname returns the input string on failure
        if ($ip !== $domain) {
            return $ip;
        }

        // Try dns_get_record for CNAME
        $records = @dns_get_record($domain, DNS_CNAME);
        if (! empty($records)) {
            // Resolve the CNAME target
            $target = $records[0]['target'] ?? null;
            if ($target) {
                $ip = gethostbyname($target);

                return $ip !== $target ? $ip : null;
            }
        }

        return null;
    }

    /**
     * Check if the domain serves our application via HTTP.
     * We check for a known response header or a redirect to /portal/login.
     */
    private function checkHttp(string $domain): bool
    {
        try {
            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 3, 'track_redirects' => true]])
                ->get("https://{$domain}/");

            // Our app redirects custom domains to /portal/login
            // or serves the landing page. Either way, a 200 or 302 is success.
            if ($response->successful() || $response->redirect()) {
                return true;
            }

            // Also check if the final URL contains /portal/login (redirect was followed)
            $body = $response->body();
            if (str_contains($body, 'DX-SchoolPortal') || str_contains($body, '/portal/login')) {
                return true;
            }

            return false;
        } catch (\Throwable) {
            // SSL not ready yet, domain not reachable, etc.
            return false;
        }
    }

    private function buildMessage(bool $dnsOk, ?string $dnsIp, bool $dnsPointsToUs, bool $httpOk, string $serverIp): string
    {
        if (! $dnsOk) {
            return 'DNS not configured — the domain does not resolve to any IP address. Please add the DNS records shown below.';
        }

        if ($dnsOk && ! $httpOk) {
            $msg = "DNS resolves to {$dnsIp}";
            if ($serverIp && ! $dnsPointsToUs) {
                $msg .= " (expected {$serverIp})";
            }
            $msg .= ', but the domain is not serving the portal yet. This could mean: SSL is still being provisioned, the domain is not added in cPanel, or DNS is pointing to the wrong server.';

            return $msg;
        }

        return 'Domain is verified and working correctly.';
    }
}
