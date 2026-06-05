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

    const data = JSON.parse(dataEl.textContent);
    const ctx  = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels:   data.labels,
            datasets: data.datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: { beginAtZero: true },
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
                        return label + ': Bs ' + value;
                    },
                },
            },
        },
    });

    const topEl     = document.getElementById('dashboard-top-data');
    const topCanvas = document.getElementById('topProductsChart');
    if (topEl && topCanvas) {
        const top = JSON.parse(topEl.textContent);
        new Chart(topCanvas.getContext('2d'), {
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
                legend: { position: 'bottom' },
                tooltips: {
                    callbacks: {
                        label: function (item, chartData) {
                            var idx = item.index;
                            var qty = chartData.datasets[0].data[idx];
                            var rev = top.revenues[idx];
                            var name = chartData.labels[idx];
                            return ' ' + name + ': ' + qty + ' uds — Bs ' +
                                parseFloat(rev).toLocaleString('es-BO', { minimumFractionDigits: 2 });
                        },
                    },
                },
            },
        });
    }
});
