<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EisService
{
    // ─────────────────────────────────────────────────────────────────────────
    //  MRA EIS API base URLs
    //
    //  Switch between dev and production via the EIS_ENVIRONMENT env variable.
    //  Add to your .env:
    //    EIS_ENVIRONMENT=development        ← use while developing / testing
    //    EIS_ENVIRONMENT=production         ← use after MRA certification
    //    EIS_PRODUCT_ID=MRA-desktop/{guid}  ← test product ID from MRA
    //    EIS_PRODUCT_VERSION=1.0.0
    // ─────────────────────────────────────────────────────────────────────────

    private const BASE_URL_DEV  = 'https://dev-eis-api.mra.mw/api/v1';
    private const BASE_URL_PROD = 'https://eis-api.mra.mw/api/v1';

    // POS product identification issued by MRA during certification.
    // During development, MRA provides a temporary test product ID.
    private const OS_NAME    = 'Linux';
    private const OS_VERSION = 'Ubuntu';
    private const OS_BUILD   = '24.04';

    // ── HTTP timeouts (seconds) ────────────────────────────────────────────
    private const CONNECT_TIMEOUT = 10;
    private const REQUEST_TIMEOUT = 30;


    // ─────────────────────────────────────────────────────────────────────────
    //  CONSTRUCTOR
    // ─────────────────────────────────────────────────────────────────────────

    private Client $http;
    private string $baseUrl;
    private string $productId;
    private string $productVersion;

    public function __construct()
    {
        $env = config('services.eis.environment', env('EIS_ENVIRONMENT', 'development'));

        $this->baseUrl        = ($env === 'production') ? self::BASE_URL_PROD : self::BASE_URL_DEV;
        $this->productId      = config('services.eis.product_id',      env('EIS_PRODUCT_ID',      'MRA-desktop/test-guid'));
        $this->productVersion = config('services.eis.product_version',  env('EIS_PRODUCT_VERSION', '1.0.0'));

        $this->http = new Client([
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'timeout'         => self::REQUEST_TIMEOUT,
            'http_errors'     => false,   // we handle HTTP errors ourselves; don't throw
            'headers'         => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  1. ACTIVATE TERMINAL
    //
    //  Sends the TAC to MRA and receives credentials + configuration.
    //  Endpoint: POST /onboarding/activate-terminal
    //
    //  On success, persists ALL returned data into branch_terminals and branches,
    //  then saves the global config into eis_global_config.
    //
    //  Returns an array with keys:
    //    'success'  bool
    //    'message'  string
    //    'data'     array|null   (the activated terminal row after update)
    // ─────────────────────────────────────────────────────────────────────────

    public function activateTerminal(int $terminalId, string $tac): array
    {
        $terminal = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('id', $terminalId)
            ->first();

        if (!$terminal) {
            return ['success' => false, 'message' => 'Terminal not found.', 'data' => null];
        }

        if ($terminal->activation_status === 'activated') {
            return ['success' => false, 'message' => 'This terminal is already activated.', 'data' => null];
        }

        // Build the MAC address for this server (used as a device fingerprint).
        // On a cloud server this will be the server's NIC MAC. MRA accepts any valid
        // 17-char MAC — it is used for device identification, not security.
        $macAddress = $this->getServerMacAddress();

        $payload = [
            'terminalActivationCode' => strtoupper(trim($tac)),
            'environment' => [
                'platform' => [
                    'osName'    => self::OS_NAME,
                    'osVersion' => self::OS_VERSION,
                    'osBuild'   => self::OS_BUILD,
                    'macAddress' => $macAddress,
                ],
                'pos' => [
                    'productID'      => $this->productId,
                    'productVersion' => $this->productVersion,
                ],
            ],
        ];

        $startTime = microtime(true);

        try {
            $response   = $this->http->post($this->baseUrl . '/onboarding/activate-terminal', [
                'json' => $payload,
            ]);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $httpStatus = $response->getStatusCode();
            $body       = json_decode($response->getBody()->getContents(), true);

        } catch (ConnectException $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $this->writeLog($terminalId, $terminal->branch_id, 'activate',
                'POST', $this->baseUrl . '/onboarding/activate-terminal',
                $this->redactPayload($payload), null, null, null, null,
                'error', 'Could not reach MRA EIS API: ' . $e->getMessage(), $durationMs);

            return ['success' => false, 'message' => 'Could not reach MRA. Check your internet connection and try again.', 'data' => null];
        }

        $mraStatusCode = $body['statusCode'] ?? null;
        $mraRemark     = $body['remark']     ?? null;
        $success       = ($httpStatus === 200 && $mraStatusCode === 1);

        // ── Log the attempt ─────────────────────────────────────────────────
        $this->writeLog(
            $terminalId, $terminal->branch_id, 'activate',
            'POST', $this->baseUrl . '/onboarding/activate-terminal',
            $this->redactPayload($payload),
            $httpStatus, $mraStatusCode, $mraRemark,
            $this->redactBody($body),
            $success ? 'success' : 'failed',
            $success ? 'Terminal activation request accepted by MRA.' : ($mraRemark ?? 'Activation failed.'),
            $durationMs
        );

        if (!$success) {
            // Mark the terminal as failed in DB
            DB::connection('tenant')->table('branch_terminals')->where('id', $terminalId)->update([
                'activation_status' => 'failed',
                'updated_at'        => now(),
            ]);

            $errorMsg = $mraRemark ?? 'Activation failed. Check the TAC and try again.';
            if (!empty($body['errors'])) {
                $errors   = collect($body['errors'])->pluck('errorMessage')->implode(' ');
                $errorMsg = $errors ?: $errorMsg;
            }
            return ['success' => false, 'message' => $errorMsg, 'data' => null];
        }

        // ── Extract all returned data ────────────────────────────────────────
        $activatedTerminal = $body['data']['activatedTerminal']    ?? [];
        $config            = $body['data']['configuration']         ?? [];
        $globalConfig      = $config['globalConfiguration']         ?? [];
        $terminalConfig    = $config['terminalConfiguration']        ?? [];
        $taxpayerConfig    = $config['taxpayerConfiguration']        ?? [];
        $credentials       = $activatedTerminal['terminalCredentials'] ?? [];

        // ── Save credentials + terminal config into branch_terminals ─────────
        DB::connection('tenant')->table('branch_terminals')->where('id', $terminalId)->update([
            'terminal_activation_code'   => strtoupper(trim($tac)),
            'mra_terminal_id'            => $activatedTerminal['terminalId']          ?? null,
            'mra_jwt_token'              => $credentials['jwtToken']                  ?? null,
            'mra_secret_key'             => $credentials['secretKey']                 ?? null,
            'mra_terminal_config_version'=> $terminalConfig['versionNo']              ?? 0,
            'offline_max_hours'          => $terminalConfig['offlineLimit']['maxTransactionAgeInHours']  ?? 0,
            'offline_max_cumulative_amount' => $terminalConfig['offlineLimit']['maxCummulativeAmount']   ?? 0,
            'activation_status'          => 'pending',   // → becomes 'activated' after confirmation
            'activated_at'               => Carbon::parse($activatedTerminal['activationDate'] ?? now()),
            'updated_at'                 => now(),
        ]);

        // ── Save taxpayer + global config into branches ──────────────────────
        DB::connection('tenant')->table('branches')->where('id', $terminal->branch_id)->update([
            'mra_global_config_version'   => $globalConfig['id']          ?? 0,
            'mra_taxpayer_config_version' => $taxpayerConfig['versionNo'] ?? 0,
            'is_vat_registered'           => (bool) ($taxpayerConfig['isVATRegistered'] ?? false),
            'mra_tax_office_code'         => $taxpayerConfig['taxOffice']['code'] ?? null,
            'mra_tax_office_name'         => $taxpayerConfig['taxOffice']['name'] ?? null,
            'activated_tax_rate_ids'      => json_encode($taxpayerConfig['activatedTaxRateIds'] ?? []),
            // If the branch TIN is blank, populate from MRA's taxpayer config
            'tin_number'                  => DB::connection('tenant')->table('branches')
                                                ->where('id', $terminal->branch_id)
                                                ->value('tin_number')
                                             ?: ($taxpayerConfig['tin'] ?? null),
            'updated_at'                  => now(),
        ]);

        // ── Save global config into eis_global_config ───────────────────────
        $this->saveGlobalConfig(
            $globalConfig,
            $taxpayerConfig['activatedLevies'] ?? [],
            $terminalId,
            'manual'
        );

        // Return the fresh terminal row for the controller
        $updated = DB::connection('tenant')->table('branch_terminals')->where('id', $terminalId)->first();

        return [
            'success' => true,
            'message' => $mraRemark ?? 'Terminal activation request sent. Please confirm activation.',
            'data'    => $updated,
        ];
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  2. CONFIRM TERMINAL ACTIVATION
    //
    //  Called immediately after a successful activateTerminal() call.
    //  Sends the x-signature (HMAC-SHA512 of TAC using secretKey) to MRA.
    //  After this, the TAC expires and the terminal is officially 'activated'.
    //
    //  Endpoint: POST /onboarding/terminal-activated-confirmation
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmActivation(int $terminalId): array
    {
        $terminal = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('id', $terminalId)
            ->first();

        if (!$terminal) {
            return ['success' => false, 'message' => 'Terminal not found.', 'data' => null];
        }

        if ($terminal->activation_status === 'activated') {
            return ['success' => false, 'message' => 'Terminal is already confirmed as activated.', 'data' => null];
        }

        if (empty($terminal->mra_terminal_id) || empty($terminal->mra_secret_key) || empty($terminal->terminal_activation_code)) {
            return ['success' => false, 'message' => 'Terminal has not been activated yet. Activate it first.', 'data' => null];
        }

        // Compute the x-signature: HMAC-SHA512(TAC, secretKey) → Base64
        $xSignature = $this->computeXSignature($terminal->terminal_activation_code, $terminal->mra_secret_key);

        $payload = ['terminalId' => $terminal->mra_terminal_id];

        $startTime = microtime(true);

        try {
            $response = $this->http->post($this->baseUrl . '/onboarding/terminal-activated-confirmation', [
                'json'    => $payload,
                'headers' => [
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'x-signature'   => $xSignature,
                ],
            ]);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $httpStatus = $response->getStatusCode();
            $body       = json_decode($response->getBody()->getContents(), true);

        } catch (ConnectException $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $this->writeLog($terminalId, $terminal->branch_id, 'confirm',
                'POST', $this->baseUrl . '/onboarding/terminal-activated-confirmation',
                $payload, null, null, null, null,
                'error', 'Could not reach MRA: ' . $e->getMessage(), $durationMs);

            return ['success' => false, 'message' => 'Could not reach MRA. Try again.', 'data' => null];
        }

        $mraStatusCode = $body['statusCode'] ?? null;
        $mraRemark     = $body['remark']     ?? null;
        $success       = ($httpStatus === 200 && ($mraStatusCode === 0 || $mraStatusCode === 1));

        $this->writeLog(
            $terminalId, $terminal->branch_id, 'confirm',
            'POST', $this->baseUrl . '/onboarding/terminal-activated-confirmation',
            $payload,
            $httpStatus, $mraStatusCode, $mraRemark,
            $body,
            $success ? 'success' : 'failed',
            $success ? 'Terminal activation confirmed.' : ($mraRemark ?? 'Confirmation failed.'),
            $durationMs
        );

        if (!$success) {
            $errorMsg = $mraRemark ?? 'Confirmation failed.';
            if (!empty($body['errors'])) {
                $errors   = collect($body['errors'])->pluck('errorMessage')->implode(' ');
                $errorMsg = $errors ?: $errorMsg;
            }
            return ['success' => false, 'message' => $errorMsg, 'data' => null];
        }

        // ── Mark terminal as fully activated ─────────────────────────────────
        DB::connection('tenant')->table('branch_terminals')->where('id', $terminalId)->update([
            'activation_status' => 'activated',
            'updated_at'        => now(),
        ]);

        $updated = DB::connection('tenant')->table('branch_terminals')->where('id', $terminalId)->first();

        return [
            'success' => true,
            'message' => 'Terminal has been confirmed and is now active.',
            'data'    => $updated,
        ];
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  3. GET LATEST CONFIG
    //
    //  Fetches the current global configuration from MRA using any activated
    //  terminal's JWT token. Updates eis_global_config and the branch + terminal
    //  config version numbers.
    //
    //  Endpoint: GET /configuration/get-latest-configs
    //
    //  The $triggerSource param is one of: 'manual' | 'scheduled' | 'reactive'
    // ─────────────────────────────────────────────────────────────────────────

    public function getLatestConfig(int $terminalId, string $triggerSource = 'manual'): array
    {
        $terminal = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('id', $terminalId)
            ->first();

        if (!$terminal) {
            return ['success' => false, 'message' => 'Terminal not found.', 'data' => null];
        }

        if ($terminal->activation_status !== 'activated') {
            return ['success' => false, 'message' => 'Terminal is not activated. Cannot fetch config.', 'data' => null];
        }

        if (empty($terminal->mra_jwt_token)) {
            return ['success' => false, 'message' => 'Terminal has no JWT token. Re-activate the terminal.', 'data' => null];
        }

        // Record the attempt timestamp regardless of outcome
        DB::connection('tenant')->table('eis_global_config')->where('id', 1)->update([
            'last_sync_attempted_at' => now(),
            'updated_at'             => now(),
        ]);

        $startTime = microtime(true);

        try {
            $response = $this->http->get($this->baseUrl . '/configuration/get-latest-configs', [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $terminal->mra_jwt_token,
                ],
            ]);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $httpStatus = $response->getStatusCode();
            $body       = json_decode($response->getBody()->getContents(), true);

        } catch (ConnectException $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            DB::connection('tenant')->table('eis_global_config')->where('id', 1)->update([
                'last_sync_status' => 'failed',
                'last_sync_error'  => 'Could not reach MRA: ' . $e->getMessage(),
                'updated_at'       => now(),
            ]);

            $this->writeLog($terminalId, $terminal->branch_id, 'get_config',
                'GET', $this->baseUrl . '/configuration/get-latest-configs',
                null, null, null, null, null,
                'error', 'Could not reach MRA: ' . $e->getMessage(), $durationMs,
                $triggerSource);

            return ['success' => false, 'message' => 'Could not reach MRA. Try again later.', 'data' => null];
        }

        $mraStatusCode = $body['statusCode'] ?? null;
        $mraRemark     = $body['remark']     ?? null;
        $success       = ($httpStatus === 200 && $mraStatusCode === 0);

        $this->writeLog(
            $terminalId, $terminal->branch_id, 'get_config',
            'GET', $this->baseUrl . '/configuration/get-latest-configs',
            null,
            $httpStatus, $mraStatusCode, $mraRemark,
            $this->redactBody($body),
            $success ? 'success' : 'failed',
            $success ? 'Configuration synced from MRA.' : ($mraRemark ?? 'Sync failed.'),
            $durationMs,
            $triggerSource
        );

        if (!$success) {
            $errorMsg = $mraRemark ?? 'Failed to fetch configuration.';
            if (!empty($body['errors'])) {
                $errors   = collect($body['errors'])->pluck('errorMessage')->implode(' ');
                $errorMsg = $errors ?: $errorMsg;
            }

            DB::connection('tenant')->table('eis_global_config')->where('id', 1)->update([
                'last_sync_status' => 'failed',
                'last_sync_error'  => $errorMsg,
                'updated_at'       => now(),
            ]);

            return ['success' => false, 'message' => $errorMsg, 'data' => null];
        }

        $globalConfig   = $body['data']['globalConfiguration']   ?? [];
        $terminalConfig = $body['data']['terminalConfiguration']  ?? [];
        $taxpayerConfig = $body['data']['taxpayerConfiguration']  ?? [];

        // ── Update eis_global_config ─────────────────────────────────────────
        $this->saveGlobalConfig(
            $globalConfig,
            $taxpayerConfig['activatedLevies'] ?? [],
            $terminalId,
            $triggerSource
        );

        // ── Update branch config versions ────────────────────────────────────
        DB::connection('tenant')->table('branches')->where('id', $terminal->branch_id)->update([
            'mra_global_config_version'   => $globalConfig['id']           ?? 0,
            'mra_taxpayer_config_version' => $taxpayerConfig['versionNo']  ?? 0,
            'is_vat_registered'           => (bool) ($taxpayerConfig['isVATRegistered'] ?? false),
            'mra_tax_office_code'         => $taxpayerConfig['taxOffice']['code'] ?? null,
            'mra_tax_office_name'         => $taxpayerConfig['taxOffice']['name'] ?? null,
            'activated_tax_rate_ids'      => json_encode($taxpayerConfig['activatedTaxRateIds'] ?? []),
            'updated_at'                  => now(),
        ]);

        // ── Update terminal config version ───────────────────────────────────
        DB::connection('tenant')->table('branch_terminals')->where('id', $terminalId)->update([
            'mra_terminal_config_version' => $terminalConfig['versionNo']   ?? 0,
            'offline_max_hours'           => $terminalConfig['offlineLimit']['maxTransactionAgeInHours']  ?? 0,
            'offline_max_cumulative_amount' => $terminalConfig['offlineLimit']['maxCummulativeAmount']    ?? 0,
            'updated_at'                  => now(),
        ]);

        $globalConfigRow = DB::connection('tenant')->table('eis_global_config')->where('id', 1)->first();

        return [
            'success' => true,
            'message' => 'Configuration synced successfully.',
            'data'    => $globalConfigRow,
        ];
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  4. PING
    //
    //  Checks whether MRA EIS API is reachable. Uses any activated terminal.
    //  Useful for the EIS dashboard connectivity indicator.
    //
    //  Endpoint: POST /utilities/ping
    // ─────────────────────────────────────────────────────────────────────────

    public function ping(int $terminalId): array
    {
        $terminal = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('id', $terminalId)
            ->where('activation_status', 'activated')
            ->first();

        if (!$terminal) {
            return ['success' => false, 'message' => 'No activated terminal found for ping.', 'reachable' => false, 'duration_ms' => null];
        }

        $startTime = microtime(true);

        try {
            $response = $this->http->post($this->baseUrl . '/utilities/ping', [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $terminal->mra_jwt_token,
                ],
                'body' => '',
            ]);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $httpStatus = $response->getStatusCode();
            $body       = json_decode($response->getBody()->getContents(), true);
            $reachable  = ($httpStatus === 200);

        } catch (ConnectException $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $this->writeLog($terminalId, $terminal->branch_id, 'ping',
                'POST', $this->baseUrl . '/utilities/ping',
                null, null, null, null, null,
                'error', 'Could not reach MRA: ' . $e->getMessage(), $durationMs);

            return ['success' => false, 'message' => 'MRA EIS API is not reachable.', 'reachable' => false, 'duration_ms' => $durationMs];
        }

        $this->writeLog(
            $terminalId, $terminal->branch_id, 'ping',
            'POST', $this->baseUrl . '/utilities/ping',
            null,
            $httpStatus, $body['statusCode'] ?? null, $body['remark'] ?? null, null,
            $reachable ? 'success' : 'failed',
            $reachable ? 'MRA API is reachable.' : 'Ping returned non-200.',
            $durationMs
        );

        return [
            'success'     => $reachable,
            'message'     => $reachable ? 'MRA EIS API is online.' : 'MRA EIS API returned an error.',
            'reachable'   => $reachable,
            'duration_ms' => $durationMs,
        ];
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Compute the x-signature for terminal activation confirmation.
     * HMAC-SHA512(activationCode, secretKey) → Base64
     */
    private function computeXSignature(string $activationCode, string $secretKey): string
    {
        $hash = hash_hmac('sha512', $activationCode, $secretKey, true);
        return base64_encode($hash);
    }

    /**
     * Upsert the single eis_global_config row (id=1).
     * Called after activateTerminal() and getLatestConfig().
     */
    private function saveGlobalConfig(
        array $globalConfig,
        array $activatedLevies,
        int   $terminalId,
        string $triggerSource
    ): void {
        DB::connection('tenant')->table('eis_global_config')->where('id', 1)->update([
            'mra_version_no'          => $globalConfig['versionNo']  ?? 0,
            'tax_rates'               => json_encode($globalConfig['taxrates'] ?? []),
            'activated_levies'        => json_encode($activatedLevies),
            'synced_via_terminal_id'  => $terminalId,
            'last_synced_at'          => now(),
            'last_sync_attempted_at'  => now(),
            'last_sync_status'        => 'ok',
            'last_sync_error'         => null,
            'updated_at'              => now(),
        ]);
    }

    /**
     * Append an immutable row to eis_terminal_logs.
     * JWT tokens in payloads are truncated to first 20 chars to avoid storing
     * full bearer tokens in the log.
     */
    private function writeLog(
        ?int    $terminalId,
        ?int    $branchId,
        string  $endpoint,
        string  $httpMethod,
        string  $url,
        ?array  $requestPayload,
        ?int    $httpStatus,
        ?int    $mraStatusCode,
        ?string $mraRemark,
        ?array  $responsePayload,
        string  $outcome,
        ?string $outcomeMessage,
        ?int    $durationMs,
        string  $triggerSource = 'manual'
    ): void {
        try {
            $userId = Auth::id();

            DB::connection('tenant')->table('eis_terminal_logs')->insert([
                'terminal_id'          => $terminalId,
                'branch_id'            => $branchId,
                'endpoint'             => $endpoint,
                'url'                  => $url,
                'http_method'          => $httpMethod,
                'request_payload'      => $requestPayload  ? json_encode($requestPayload)  : null,
                'http_status'          => $httpStatus,
                'mra_status_code'      => $mraStatusCode,
                'mra_remark'           => $mraRemark ? substr($mraRemark, 0, 499) : null,
                'response_payload'     => $responsePayload ? json_encode($responsePayload) : null,
                'outcome'              => $outcome,
                'outcome_message'      => $outcomeMessage ? substr($outcomeMessage, 0, 499) : null,
                'duration_ms'          => $durationMs,
                'triggered_by_user_id' => $userId,
                'trigger_source'       => $triggerSource,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        } catch (\Exception $e) {
            // Never let a logging failure crash the actual operation.
            // Silently swallow — the calling method's response is more important.
        }
    }

    /**
     * Redact sensitive fields from a request payload before logging.
     * Replaces full JWT tokens with a truncated version.
     */
    private function redactPayload(array $payload): array
    {
        // The activation payload is safe — it only contains the TAC and environment info.
        // Nothing to redact on the way out.
        return $payload;
    }

    /**
     * Redact sensitive fields from a response body before logging.
     * Truncates JWT tokens to first 30 chars + "..." so they aren't stored in logs.
     */
    private function redactBody(?array $body): ?array
    {
        if (!$body) {
            return null;
        }

        // Deep walk and truncate any string value that looks like a JWT
        array_walk_recursive($body, function (&$value) {
            if (is_string($value) && strlen($value) > 80 && substr_count($value, '.') >= 2) {
                $value = substr($value, 0, 30) . '...[redacted]';
            }
        });

        return $body;
    }

    /**
     * Returns the server's MAC address for the activation environment payload.
     * Falls back to a zeroed MAC if it cannot be determined.
     */
    private function getServerMacAddress(): string
    {
        try {
            // Try to read from /sys/class/net on Linux
            $interfaces = @file('/sys/class/net/eth0/address');
            if ($interfaces && !empty($interfaces[0])) {
                $mac = strtoupper(trim($interfaces[0]));
                // Convert colon-separated to hyphen-separated (MRA format)
                return str_replace(':', '-', $mac);
            }
        } catch (\Exception $e) {
            // ignore
        }

        // Fallback: use a deterministic value based on the app key hash.
        // This is stable across requests but not a real MAC.
        $hash = md5(config('app.key', 'netacube'));
        return strtoupper(implode('-', str_split(substr($hash, 0, 12), 2)));
    }
}