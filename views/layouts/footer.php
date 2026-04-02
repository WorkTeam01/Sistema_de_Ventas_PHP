<!-- Main Footer -->
<footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
        System Ventas
    </div>
    <!-- Default to the left -->
    <strong>Copyright &copy; <?= $Año; ?> <a href="https://adminlte.io">AdminLTE.io</a></strong> | Todos los derechos reservados
</footer>

<!-- Control sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
<div id="sidebar-overlay"></div>
</div>
<!-- ./wrapper -->

<script src="<?= BASE_URL ?>/js/core/control_sidebar.js"></script>

<!-- Bootstrap 4 -->
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>
<?php if (in_array('datatable', $assets ?? [])) : ?>
<!-- DataTables & Plugins -->
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/jszip/jszip.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/pdfmake/pdfmake.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/pdfmake/vfs_fonts.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<?php endif; ?>
<?php if (in_array('select2', $assets ?? [])) : ?>
<!-- Select2 -->
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/select2/js/select2.min.js"></script>
<?php endif; ?>
<?php if (in_array('validation', $assets ?? [])) : ?>
<!-- jQuery Validate -->
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/jquery-validation/localization/messages_es.min.js"></script>
<?php endif; ?>
<!-- UI Components Utils (General UI Helpers) -->
<script src="<?= BASE_URL; ?>/js/core/ui-components.js"></script>

</body>

</html>