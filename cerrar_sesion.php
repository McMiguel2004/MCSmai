<?php
// Iniciar la sesión
session_start();

// Eliminar todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al usuario a la página de inicio
header("Location: index.html");
exit; // Asegura que el script se detenga aquí y que la redirección se ejecute correctamente
?>