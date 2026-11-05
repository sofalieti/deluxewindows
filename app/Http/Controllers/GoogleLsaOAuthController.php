<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Ads\GoogleLsaClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GoogleLsaOAuthController extends Controller
{
    public function __invoke(Request $request, GoogleLsaClient $lsa): Response
    {
        $code = trim((string) $request->query('code', ''));

        try {
            $refreshToken = $lsa->exchangeAuthorizationCode($code);
            GoogleLsaClient::upsertEnvFile($lsa->envPath(), [
                'GOOGLE_LSA_REFRESH_TOKEN' => $refreshToken,
            ]);
            config()->set('services.google_lsa.refresh_token', $refreshToken);
            Cache::forget('google-lsa:access-token');
        } catch (Throwable $exception) {
            return $this->page('Local Services was not connected', $exception->getMessage());
        }

        try {
            $accounts = $lsa->accountReports();
            $leads = $lsa->detailedLeadReports(5);
        } catch (Throwable $exception) {
            return $this->page('Token saved. The account report did not load.', $exception->getMessage());
        }

        $lines = [];
        foreach ($accounts as $account) {
            $lines[] = sprintf(
                '%s — charged leads %s, phone calls %s, cost %s',
                (string) ($account['businessName'] ?? $account['accountId'] ?? '?'),
                $account['currentPeriodChargedLeads'] ?? '0',
                $account['currentPeriodPhoneCalls'] ?? '0',
                $account['currentPeriodTotalCost'] ?? '0'
            );
        }
        if ($lines === []) {
            $lines[] = 'The API answered, and this manager has no Local Services accounts.';
        }
        $lines[] = count($leads).' recent leads';

        return $this->page('Local Services is connected', implode("\n", $lines));
    }

    private function page(string $title, string $body): Response
    {
        $html = '<!doctype html><html><head><meta name="robots" content="noindex"><title>'
            .e($title)
            .'</title></head><body><pre style="font:16px/1.5 sans-serif;padding:24px">'
            .e($title)."\n\n".e($body)
            .'</pre></body></html>';

        return response($html);
    }
}
