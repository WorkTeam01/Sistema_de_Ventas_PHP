<?php
// Consolidado en AuthController — redirige a la nueva ruta
require_once '../../config.php';
header('Location: ' . $URL . '/auth/logout');
exit();
