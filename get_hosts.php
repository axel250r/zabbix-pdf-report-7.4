<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['zbx_auth_ok'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Sesión inválida']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/ZabbixApi.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Usar las credenciales de la SESIÓN y respetar VERIFY_SSL
    $api = new ZabbixApi(
        ZABBIX_API_URL, 
        (string)$_SESSION['zbx_user'], 
        (string)$_SESSION['zbx_pass'], 
        30, 
        (defined('VERIFY_SSL') ? VERIFY_SSL : false)
    );
    $hosts = $api->call('host.get', [
        'output'    => ['hostid','name','host'],
        'sortfield' => 'name'
    ]);
    echo json_encode(is_array($hosts) ? $hosts : []);
} catch (Throwable $e) {
    // --- CORRECCIÓN DE DEBUG ---
    // Devolvemos el error real a la interfaz
    http_response_code(500);
    echo json_encode(['error' => 'Error API en get_hosts.php: ' . $e->getMessage()]);
}