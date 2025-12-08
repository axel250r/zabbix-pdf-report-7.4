<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['zbx_auth_ok'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sesión inválida']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/ZabbixApi.php';

try {
    // --- INICIO DE CORRECCIÓN ---
    // Usamos las credenciales de la sesión y pasamos el setting de VERIFY_SSL
    $api = new ZabbixApi(
        ZABBIX_API_URL, 
        (string)$_SESSION['zbx_user'], 
        (string)$_SESSION['zbx_pass'],
        30,
        (defined('VERIFY_SSL') ? VERIFY_SSL : false)
    );
    // --- FIN DE CORRECCIÓN ---

    $templateids = [];
    if (!empty($_GET['templateids'])) {
        $raw = is_array($_GET['templateids']) ? $_GET['templateids'] : explode(',', (string)$_GET['templateids']);
        foreach ($raw as $v) {
            $v = trim($v);
            if ($v !== '' && ctype_digit($v)) $templateids[] = $v;
        }
    }

    if (empty($templateids)) {
        echo json_encode([]);
        exit;
    }
    
    $params = [
        'output'      => ['itemid', 'name', 'key_'],
        'sortfield'   => 'name',
        'templateids' => $templateids,
        'templated'   => true, // Nos aseguramos de que son ítems de plantilla
        'filter'      => ['flags' => 0] // La clave: 0 = ítem normal. 4 = ítem descubierto (LLD).
    ];

    if (!empty($_GET['filter'])) {
        $params['search'] = ['name' => (string)$_GET['filter']];
        $params['searchByAny'] = true;
    } else {
        $params['limit'] = 200; // Aumentamos un poco el límite ya que no hay prototipos
    }

    // Hacemos una única llamada a la API para obtener solo los ítems normales
    $items = $api->call('item.get', $params);
    
    echo json_encode(is_array($items) ? $items : []);

} catch (Throwable $e) {
    error_log("Zabbix PDF Report - API Error en get_items.php: " . $e->getMessage());
    // --- INICIO DE CORRECCIÓN (DEBUG) ---
    // Devolvemos el error real a la interfaz para depuración
    http_response_code(500);
    echo json_encode(['error' => 'Error API en get_items.php: ' . $e->getMessage()]);
    // --- FIN DE CORRECCIÓN (DEBUG) ---
}