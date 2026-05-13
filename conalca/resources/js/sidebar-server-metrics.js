import Chart from 'chart.js/auto';

const MAX_POINTS = 24;
const REFRESH_INTERVAL = 3000;

document.addEventListener('DOMContentLoaded', () => {
    const widget = document.getElementById('serverMetricsWidget');
    const canvas = document.getElementById('serverMetricsChart');

    if (!widget || !canvas) {
        return;
    }

    const metricsUrl = widget.dataset.metricsUrl;
    const cpuValue = document.getElementById('serverCpuValue');
    const ramValue = document.getElementById('serverRamValue');
    const cpuBar = document.getElementById('serverCpuBar');
    const ramBar = document.getElementById('serverRamBar');
    const ramDetail = document.getElementById('serverRamDetail');
    const updated = document.getElementById('serverMetricsUpdated');
    const status = document.getElementById('serverMetricsStatus');
    const statusDot = document.getElementById('serverMetricsDot');

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'CPU',
                    data: [],
                    borderColor: '#FF7C32',
                    backgroundColor: 'rgba(255, 124, 50, 0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                },
                {
                    label: 'RAM',
                    data: [],
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37, 99, 235, 0.05)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.35,
                    pointRadius: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(46, 44, 52, 0.95)',
                    displayColors: true,
                    callbacks: {
                        label(context) {
                            return `${context.dataset.label}: ${Math.round(context.parsed.y)}%`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    display: false,
                },
                y: {
                    display: false,
                    min: 0,
                    max: 100,
                },
            },
        },
    });

    if ('ResizeObserver' in window) {
        new ResizeObserver(() => chart.resize()).observe(widget);
    }

    const setStatus = (isOnline) => {
        if (status) {
            status.textContent = isOnline ? 'En vivo' : 'Sin datos';
        }

        if (statusDot) {
            statusDot.classList.toggle('bg-[#FF7C32]', isOnline);
            statusDot.classList.toggle('bg-gray-400', !isOnline);
        }
    };

    const setPercent = (element, value) => {
        if (element) {
            element.textContent = `${Math.round(value)}%`;
        }
    };

    const setBar = (element, value) => {
        if (element) {
            element.style.width = `${Math.min(Math.max(value, 0), 100)}%`;
        }
    };

    const pushPoint = (label, cpuPercent, ramPercent) => {
        chart.data.labels.push(label);
        chart.data.datasets[0].data.push(cpuPercent);
        chart.data.datasets[1].data.push(ramPercent);

        while (chart.data.labels.length > MAX_POINTS) {
            chart.data.labels.shift();
            chart.data.datasets.forEach((dataset) => dataset.data.shift());
        }

        chart.update('none');
    };

    const updateMetrics = async () => {
        if (document.hidden) {
            return;
        }

        try {
            const response = await fetch(metricsUrl, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Metrics request failed: ${response.status}`);
            }

            const metrics = await response.json();
            const cpuPercent = Number(metrics.cpu?.usage_percent ?? 0);
            const ramPercent = Number(metrics.memory?.usage_percent ?? 0);
            const now = new Date();
            const timeLabel = now.toLocaleTimeString('es-CO', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });

            setPercent(cpuValue, cpuPercent);
            setPercent(ramValue, ramPercent);
            setBar(cpuBar, cpuPercent);
            setBar(ramBar, ramPercent);

            if (ramDetail) {
                ramDetail.textContent = `${metrics.memory?.used_label ?? '--'} / ${metrics.memory?.total_label ?? '--'}`;
            }

            if (updated) {
                updated.textContent = timeLabel;
            }

            pushPoint(timeLabel, cpuPercent, ramPercent);
            setStatus(true);
        } catch (error) {
            setStatus(false);
        }
    };

    updateMetrics();
    setInterval(updateMetrics, REFRESH_INTERVAL);
});