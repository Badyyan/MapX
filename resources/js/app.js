import Chart from 'chart.js/auto';

window.Chart = Chart;

// Sensible chart defaults shared by every analytics view.
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = 'rgba(100, 116, 139, .15)';
Chart.defaults.plugins.legend.labels.boxWidth = 12;

// Render every canvas that declares a chart config in data-chart.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        try {
            new Chart(canvas, JSON.parse(canvas.dataset.chart));
        } catch (e) {
            console.error('Chart init failed', e);
        }
    });
});
