jQuery(document).ready(function($) {
    // بررسی وجود siwo_report_data
    if (typeof siwo_report_data === 'undefined') {
        console.log('siwo_report_data is not defined.');
        return;
    }

    // ایجاد نمودار فروش
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesData = {
        labels: Object.keys(siwo_report_data.sales_by_date),
        datasets: [{
            label: siwo_report_data.labels.sales, // استفاده از متن ترجمه‌شده
            data: Object.values(siwo_report_data.sales_by_date),
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            fill: true,
        }]
    };

    new Chart(ctx, {
        type: 'line',
        data: salesData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return siwo_report_data.currency + value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + siwo_report_data.currency + context.parsed.y;
                        }
                    }
                }
            }
        }
    });
});