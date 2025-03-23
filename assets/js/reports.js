jQuery(document).ready(function($) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesData = Object.values(siwo_report_data.sales_by_date);
    const salesLabels = Object.keys(siwo_report_data.sales_by_date);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'Sales (' + siwo_report_data.currency + ')',
                data: salesData,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});