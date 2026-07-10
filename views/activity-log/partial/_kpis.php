<div class="row">
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Eventos en el rango</span>
                <span class="info-box-number"><?= (int)$kpis['total'] ?></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Usuarios distintos activos</span>
                <span class="info-box-number"><?= (int)$kpis['usuarios_distintos'] ?></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-trash"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Eliminaciones</span>
                <span class="info-box-number"><?= (int)$kpis['eliminaciones'] ?></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cambios sensibles</span>
                <span class="info-box-number"><?= (int)$kpis['cambios_sensibles'] ?></span>
            </div>
        </div>
    </div>
</div>
