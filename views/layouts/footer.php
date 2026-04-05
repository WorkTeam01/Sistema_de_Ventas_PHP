</main>

<!-- Main Footer -->
<footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
        <small class="text-muted">
            <i class="fas fa-tag mr-1"></i>v<?= APP_VERSION ?>
        </small>
    </div>
    <strong>Copyright &copy; <?= date('Y') ?>
        <a href="#" class="text-decoration-none">Sistema de Ventas</a>
    </strong>
    &mdash; Todos los derechos reservados.
</footer>

<!-- Control sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
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
<?php if (in_array('chart', $assets ?? [])) : ?>
    <!-- Chart.js -->
    <script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/chart.js/Chart.min.js"></script>
<?php endif; ?>
<!-- Page specific scripts -->
<?php if (isset($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= BASE_URL; ?><?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<!-- UI Components Utils (General UI Helpers) -->
<script src="<?= BASE_URL; ?>/js/core/ui-components.js"></script>

</body>

</html>