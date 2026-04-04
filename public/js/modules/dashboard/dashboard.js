document.addEventListener('DOMContentLoaded', function () {
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
});
