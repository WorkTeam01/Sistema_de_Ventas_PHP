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
<script src="<?= BASE_URL ?>/js/lib/bootstrap/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?= BASE_URL ?>/js/lib/adminlte/adminlte.min.js"></script>
<?php if (in_array('datatable', $assets ?? [])) : ?>
    <!-- DataTables & Plugins -->
    <script src="<?= BASE_URL ?>/js/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/datatables/responsive.bootstrap4.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/datatables/buttons.bootstrap4.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/jszip/jszip.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/pdfmake/pdfmake.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/pdfmake/vfs_fonts.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/datatables/buttons.html5.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/datatables/buttons.print.min.js"></script>
    <script src="<?= BASE_URL ?>/js/plugins/datatables/buttons.colVis.min.js"></script>
<?php endif; ?>
<?php if (in_array('select2', $assets ?? [])) : ?>
    <!-- Select2 -->
    <script src="<?= BASE_URL ?>/js/plugins/select2/select2.min.js"></script>
<?php endif; ?>
<?php if (in_array('validation', $assets ?? [])) : ?>
    <!-- jQuery Validate -->
    <script src="<?= BASE_URL ?>/js/lib/jquery/jquery.validate.min.js"></script>
    <script src="<?= BASE_URL ?>/js/lib/jquery/messages_es.min.js"></script>
<?php endif; ?>
<?php if (in_array('chart', $assets ?? [])) : ?>
    <!-- Chart.js -->
    <script src="<?= BASE_URL ?>/js/plugins/chart/Chart.min.js"></script>
<?php endif; ?>
<!-- Page specific scripts -->
<?php if (isset($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= BASE_URL; ?><?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<!-- Mostrar/ocultar contraseña (global) -->
<script src="<?= BASE_URL; ?>/js/core/password-toggle.js"></script>
<!-- UI Components Utils (General UI Helpers) -->
<script src="<?= BASE_URL; ?>/js/core/ui-components.js"></script>

</body>

</html>