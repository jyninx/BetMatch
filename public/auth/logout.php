<?php
// Arranco la sesion para poder cerrarla despues
session_start();

// Borro todas las variables de la sesion actual
session_destroy();

// Le mando a la pagina de login otra vez
header('Location: login.php');
exit();
?>