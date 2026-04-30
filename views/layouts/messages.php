<?php

if (isset($_SESSION['welcome_user'])):
    $welcomeName = $_SESSION['welcome_user'];
    unset($_SESSION['welcome_user']);
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') Swal.close();
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
        document.addEventListener('DOMContentLoaded', function () {
            const type = <?= json_encode($icono) ?>;
            const msg  = <?= json_encode($respuesta) ?>;
            if (typeof ToastUtils !== 'undefined' && typeof ToastUtils[type] === 'function') {
                ToastUtils[type](msg);
            }
        });
    </script>
<?php endif; ?>