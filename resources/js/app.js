import Chart from 'chart.js/auto';

window.Chart = Chart;

// ---- Theme --------------------------------------------------------------
// Applied before paint by the inline <head> script; this module keeps the
// charts and the toggle in sync.
function isDark() {
    return document.documentElement.classList.contains('dark');
}

function applyChartTheme() {
    Chart.defaults.color = isDark() ? '#98989d' : '#6e6e73';
    Chart.defaults.borderColor = isDark() ? 'rgba(255,255,255,.08)' : 'rgba(110,110,115,.15)';
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
}

function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        try {
            Chart.getChart(canvas)?.destroy();
            new Chart(canvas, JSON.parse(canvas.dataset.chart));
        } catch (e) {
            console.error('Chart init failed', e);
        }
    });
}

window.toggleTheme = () => {
    const dark = document.documentElement.classList.toggle('dark');
    localStorage.theme = dark ? 'dark' : 'light';
    document.querySelectorAll('[data-theme-icon]').forEach((el) => {
        el.classList.toggle('hidden', el.dataset.themeIcon !== (dark ? 'dark' : 'light'));
    });
    applyChartTheme();
    renderCharts();
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-theme-icon]').forEach((el) => {
        el.classList.toggle('hidden', el.dataset.themeIcon !== (isDark() ? 'dark' : 'light'));
    });
    applyChartTheme();
    renderCharts();
});
