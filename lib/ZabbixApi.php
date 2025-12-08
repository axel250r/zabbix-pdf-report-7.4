<?php
/**
 * lib/ZabbixApi.php — versión para Zabbix 7.x
 * - NO manda "auth" en el body → evita "unexpected parameter \"auth\""
 * - Acepta 4to parámetro como ARRAY (como lo usa tu export.php)
 * - user.login sin bearer
 * - resto de métodos con header Authorization: Bearer <token>
 */
class ZabbixApi
{
    private string $url;
    private ?string $username = null;
    private ?string $password = null;
    private ?string $token    = null;

    private int  $timeout   = 30;
    private bool $verifySsl = false;
    private array $extraHeaders = [];

    /**
     * Formas válidas:
     *  new ZabbixApi($url, $user, $pass);
     *  new ZabbixApi($url, $user, $pass, 30);
     *  new ZabbixApi($url, $user, $pass, ['timeout'=>30,'verify_ssl'=>false]);
     */
    public function __construct(string $url, ?string $user = null, ?string $pass = null, $options = null)
    {
        $this->url      = rtrim($url, '/');
        $this->username = $user;
        $this->password = $pass;

        if (is_array($options)) {
            if (isset($options['timeout'])) {
                $this->timeout = (int)$options['timeout'];
            }
            if (isset($options['verify_ssl'])) {
                $this->verifySsl = (bool)$options['verify_ssl'];
            }
            if (!empty($options['headers']) && is_array($options['headers'])) {
                $this->extraHeaders = $options['headers'];
            }
        } elseif (is_int($options)) {
            $this->timeout = $options;
        }

        // si ya me pasan user/pass, logueo
        if (!empty($this->username) && !empty($this->password)) {
            $this->login();
        }
    }

    /**
     * Login clásico de Zabbix:
     * - no lleva "auth"
     */
    public function login(): string
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method'  => 'user.login',
            'params'  => [
                'username' => $this->username,
                'password' => $this->password,
            ],
            'id'      => 1,
        ];

        $resp = $this->doRequest($payload, false); // false = sin bearer

        if (!isset($resp['result']) || !is_string($resp['result']) || $resp['result'] === '') {
            throw new \RuntimeException('Login falló: token vacío');
        }

        $this->token = $resp['result'];
        return $this->token;
    }

    /**
     * Llamada normal (autenticada)
     */
    public function call(string $method, array $params = [])
    {
        if ($method !== 'user.login' && $this->token === null) {
            $this->login();
        }

        $payload = [
            'jsonrpc' => '2.0',
            'method'  => $method,
            'params'  => $params,
            'id'      => 1,
        ];

        $resp = $this->doRequest($payload, $method !== 'user.login');
        return $resp['result'] ?? null;
    }

    /**
     * POST a la API
     */
    private function doRequest(array $payload, bool $withBearer): array
    {
        // si no viene completo, agregamos api_jsonrpc.php
        $apiUrl = $this->url;
        if (stripos($apiUrl, 'api_jsonrpc.php') === false) {
            $apiUrl .= '/api_jsonrpc.php';
        }

        $ch = curl_init($apiUrl);

        $headers = ['Content-Type: application/json-rpc; charset=UTF-8'];
        if ($withBearer && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        if ($this->extraHeaders) {
            $headers = array_merge($headers, $this->extraHeaders);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl ? 1 : 0,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
        ]);

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('HTTP error: ' . $err);
        }
        if ($code !== 200 || !$resp) {
            throw new \RuntimeException('HTTP error: ' . $code);
        }

        $json = json_decode($resp, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Respuesta API no válida');
        }
        if (isset($json['error'])) {
            throw new \RuntimeException('API error: ' . json_encode($json['error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $json;
    }

    /* ======================================================
     *  MÉTODOS QUE TU generate.php ESTÁ USANDO
     * ====================================================== */

    /**
     * Este era el que faltaba: generate.php llama a esto.
     */
    public function hostGetBasicByIds(array $hostids): array
    {
        if (empty($hostids)) {
            return [];
        }
        return (array)$this->call('host.get', [
            'output'  => ['hostid', 'host', 'name'],
            'hostids' => $hostids,
            'sortfield' => 'name',
        ]);
    }

    public function hostIdsByGroupIds(array $groupids): array
    {
        if (empty($groupids)) {
            return [];
        }
        return (array)$this->call('host.get', [
            'output'   => ['hostid', 'name', 'host'],
            'groupids' => $groupids,
        ]);
    }

    public function hostMapByNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }
        $res = $this->call('host.get', [
            'output' => ['hostid','host','name'],
            'filter' => ['host' => $names],
        ]);

        $map = [];
        foreach ((array)$res as $h) {
            $k = $h['host'] ?? ($h['name'] ?? '');
            if ($k !== '' && isset($h['hostid'])) {
                $map[$k] = $h['hostid'];
            }
        }
        return $map;
    }

    public function itemsByTemplateIds(array $templateids): array
    {
        if (empty($templateids)) return [];
        return (array)$this->call('item.get', [
            'output'      => ['itemid','name','key_'],
            'templateids' => $templateids,
            'sortfield'   => 'name',
        ]);
    }

    public function graphGetByHostIds(array $hostids): array
    {
        if (empty($hostids)) return [];
        return (array)$this->call('graph.get', [
            'output'    => ['graphid','name'],
            'hostids'   => $hostids,
            'sortfield' => 'name',
        ]);
    }
}
