<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CpanelService
{
    private $domain;
    private $username;
    private $apiToken;
    private $port;

    public function __construct()
    {
        $this->domain   = config('cpanel.domain');
        $this->username = config('cpanel.username');
        $this->apiToken = config('cpanel.api_token');
        $this->port     = config('cpanel.port', 2083);
    }

    /**
     * Make an API request to cPanel UAPI
     */
    private function makeRequest($module, $function, $params = [])
    {
        $url = "https://{$this->domain}:{$this->port}/execute/{$module}/{$function}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: cpanel {$this->username}:{$this->apiToken}",
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            return ['success' => false, 'message' => "cURL error: $error"];
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200 || !isset($result['status']) || $result['status'] != 1) {
            $message = isset($result['errors']) ? implode(', ', $result['errors']) : json_encode($result);
            return ['success' => false, 'message' => $message];
        }

        return ['success' => true, 'data' => $result['data'] ?? null];
    }

    /** Check if database exists */
    public function databaseExists($databaseName)
    {
        $result = $this->makeRequest('Mysql', 'list_databases');
        if (!$result['success']) {
            Log::error("Failed to list databases: " . $result['message']);
            return false;
        }

        foreach ($result['data']['databases'] ?? [] as $db) {
            if ($db['database'] === $databaseName) {
                return true;
            }
        }
        return false;
    }

    /** Create database */
    public function createDatabase($databaseName)
    {
        return $this->makeRequest('Mysql', 'create_database', [
            'name' => $databaseName,
        ]);
    }

    /** Delete database */
    public function deleteDatabase($databaseName)
    {
        return $this->makeRequest('Mysql', 'delete_database', [
            'name' => $databaseName,
        ]);
    }

    /** Create database user */
    public function createUser($username, $password)
    {
        return $this->makeRequest('Mysql', 'create_user', [
            'name'     => $username,
            'password' => $password,
        ]);
    }

    /** Delete database user */
    public function deleteUser($username)
    {
        return $this->makeRequest('Mysql', 'delete_user', [
            'name' => $username,
        ]);
    }

    /** Assign privileges */
    public function setPrivileges($username, $databaseName)
    {
        return $this->makeRequest('Mysql', 'set_privileges_on_database', [
            'user'       => $username,
            'database'   => $databaseName,
            'privileges' => 'ALL PRIVILEGES',
        ]);
    }
}
