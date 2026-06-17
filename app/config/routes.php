<?php

// Creo un array para guardar todas las rutas de la pagina
$todas_las_rutas = array(
    // Rutas de autenticación
    'login' => array('controller' => 'AuthController', 'action' => 'loginForm'),
    'authenticate' => array('controller' => 'AuthController', 'action' => 'authenticate'),
    'register' => array('controller' => 'AuthController', 'action' => 'registerForm'),
    'process-register' => array('controller' => 'AuthController', 'action' => 'processRegister'),
    
    // Rutas de recuperación de contraseña
    'forgot-password' => array('controller' => 'AuthController', 'action' => 'forgotPasswordForm'),
    'process-forgot-password' => array('controller' => 'AuthController', 'action' => 'processForgotPassword'),
    'reset-password' => array('controller' => 'AuthController', 'action' => 'resetPasswordForm'),
    'process-reset-password' => array('controller' => 'AuthController', 'action' => 'processResetPassword'),
    
    // Ruta para cerrar sesion
    'logout' => array('controller' => 'AuthController', 'action' => 'logout'),
);

// Devuelvo las rutas para usarlas en otra parte
return $todas_las_rutas;

?>