<?php

if (isset($_SESSION['welcome_user'])):
    $welcomeName = $_SESSION['welcome_user'];
    unset($_SESSION['welcome_user']);
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined' && Swal.isVisible && Swal.isVisible()) {
                Swal.close();
            }
            if (typeof AlertUtils !== 'undefined') {
                AlertUtils.welcome(<?= json_encode($welcomeName) ?>);
            }
        });
    </script>
<?php endif; ?>

<?php if (isset($_SESSION['mensaje']) && isset($_SESSION['icono'])): ?>
    <?php
    $respuesta = $_SESSION['mensaje'];
    $icono     = $_SESSION['icono'];
    unset($_SESSION['mensaje'], $_SESSION['icono']);
    ?>
    <script>
        showToast(<?= json_encode($icono) ?>, <?= json_encode($respuesta) ?>);
    </script>
<?php endif; ?>