<?php

if ((isset($_SESSION['mensaje'])) && (isset($_SESSION['icono']))) {
    $respuesta = $_SESSION['mensaje'];
    $icono = $_SESSION['icono']; ?>
    <script>
        Swal.fire({
            position: 'top-end',
            text: "<?php echo $respuesta; ?>",
            icon: "<?php echo $icono; ?>",
            showConfirmButton: false,
            timer: 2000
        });
    </script>
<?php
    unset($_SESSION['mensaje']);
    unset($_SESSION['icono']);
} ?>