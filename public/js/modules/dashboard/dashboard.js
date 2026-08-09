document.addEventListener('DOMContentLoaded', function () {
    const adminKpisToggle = document.getElementById('admin-kpis-toggle');
    const adminKpisIcon   = document.getElementById('admin-kpis-icon');
    if (adminKpisToggle && adminKpisIcon) {
        adminKpisToggle.addEventListener('click', function () {
            adminKpisIcon.classList.toggle('fa-arrow-up');
            adminKpisIcon.classList.toggle('fa-arrow-down');
        });
    }

    const dataEl = document.getElementById('dashboard-chart-data');
    const canvas = document.getElementById('salesPurchasesChart');

    if (!dataEl || !canvas) return;

    const data     = JSON.parse(dataEl.textContent);
    const currency = canvas.dataset.currency || '';
    const ctx      = canvas.getContext('2d');

    function isDarkMode() {
        return document.body.classList.contains('dark-mode');
    }

    function chartTextColor() {
        return isDarkMode() ? '#ced4da' : '#495057';
    }

    function chartGridColor() {
        return isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    }

    const barChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels:   data.labels,
            datasets: data.datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                labels: { fontColor: chartTextColor() },
            },
            scales: {
                xAxes: [{
                    ticks: { fontColor: chartTextColor() },
                    gridLines: { color: chartGridColor() },
                }],
                yAxes: [{
                    ticks: { beginAtZero: true, fontColor: chartTextColor() },
                    gridLines: { color: chartGridColor() },
                }],
            },
            tooltips: {
                callbacks: {
                    label: function (item, chartData) {
                        var label = chartData.datasets[item.datasetIndex].label || '';
                        var value = parseFloat(item.yLabel).toLocaleString('es-BO', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });
                        return label + ': ' + currency + ' ' + value;
                    },
                },
            },
        },
    });

    const topEl     = document.getElementById('dashboard-top-data');
    const topCanvas = document.getElementById('topProductsChart');
    let doughnutChart = null;
    if (topEl && topCanvas) {
        const top         = JSON.parse(topEl.textContent);
        const topCurrency = topCanvas.dataset.currency || '';
        doughnutChart = new Chart(topCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: top.labels,
                datasets: [{
                    data: top.quantities,
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1'],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom',
                    labels: { fontColor: chartTextColor() },
                },
                tooltips: {
                    callbacks: {
                        label: function (item, chartData) {
                            var idx = item.index;
                            var qty = chartData.datasets[0].data[idx];
                            var rev = top.revenues[idx];
                            var name = chartData.labels[idx];
                            return ' ' + name + ': ' + qty + ' uds — ' + topCurrency + ' ' +
                                parseFloat(rev).toLocaleString('es-BO', { minimumFractionDigits: 2 });
                        },
                    },
                },
            },
        });
    }

    // El toggle de "Modo Oscuro" (control_sidebar.js) cambia body.dark-mode en vivo,
    // sin recargar la página — hay que re-pintar los charts o quedan con colores del modo anterior.
    // Otros toggles del sidebar (colapsar, sidebar-mini, etc.) también mutan `class` en body,
    // por eso se compara el estado real de dark-mode en vez de reaccionar a cualquier cambio.
    let wasDarkMode = isDarkMode();
    new MutationObserver(function () {
        const nowDarkMode = isDarkMode();
        if (nowDarkMode === wasDarkMode) return;
        wasDarkMode = nowDarkMode;

        const textColor = chartTextColor();
        const gridColor = chartGridColor();

        barChart.options.legend.labels.fontColor = textColor;
        barChart.options.scales.xAxes[0].ticks.fontColor = textColor;
        barChart.options.scales.xAxes[0].gridLines.color = gridColor;
        barChart.options.scales.yAxes[0].ticks.fontColor = textColor;
        barChart.options.scales.yAxes[0].gridLines.color = gridColor;
        barChart.update();

        if (doughnutChart) {
            doughnutChart.options.legend.labels.fontColor = textColor;
            doughnutChart.update();
        }
    }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
});
