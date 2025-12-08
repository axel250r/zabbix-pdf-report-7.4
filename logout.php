<?php
declare(strict_types=1);

// Es crucial iniciar la sesión para poder manipularla.
session_start();

// 1. Limpiar el archivo de cookie jar temporal (esto ya lo hacías y es excelente).
$cookie = $_SESSION['zbx_cookiejar'] ?? '';
if ($cookie && is_file($cookie)) {
    @unlink($cookie);
}

// 2. Limpiar todas las variables de la sesión del script actual.
$_SESSION = [];

// 3. (MEJORA) Borrar la cookie de sesión del navegador.
// Esto se hace enviando una cookie con el mismo nombre, pero con una fecha de expiración en el pasado.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finalmente, destruir la sesión en el servidor.
session_destroy();

// 5. Redirigir al login y detener el script.
header('Location: login.php');
exit(); // Es una buena práctica añadir exit() después de una redirección.