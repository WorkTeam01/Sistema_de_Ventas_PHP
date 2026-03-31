<?php

if (isset($_SESSION['mensaje']) && isset($_SESSION['icono'])):
    $respuesta = $_SESSION['mensaje'];
    $icono     = $_SESSION['icono'];
    unset($_SESSION['mensaje'], $_SESSION['icono']);
?>
    <script>
        showToast(<?= json_encode($icono) ?>, <?= json_encode($respuesta) ?>);
    </script>
<?php endif; ?>