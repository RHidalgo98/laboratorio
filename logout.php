<?php
// Iniciar sesión si no está iniciada
session_start();

// Eliminar todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Redirigir al formulario de login
header("Location: login.html");
exit(); // Asegurar que el script se detenga después de redirigir
?>
