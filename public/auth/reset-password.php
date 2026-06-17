<?php
// Si el token viene por la URL
if (isset($_GET['token'])) {
    // Le mando a forgot-password con el token para que siga desde ahi
    $url_con_el_token = 'Location: forgot-password.php?token=' . urlencode($_GET['token']);
    header($url_con_el_token);
} else {
    // Si no trae token, lo mando a pelo
    header('Location: forgot-password.php');
}
// Salgo para que no se ejecute nada mas
exit();
?>