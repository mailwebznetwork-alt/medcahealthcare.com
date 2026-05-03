import ApexCharts from 'apexcharts';

const gold = '#d4a95f';
const grid = 'rgba(255,255,255,0.03)';
const muted = '#9a9a9a';

/**
 * @returns {import('apexcharts').default | null}
 */
function mountAnalyticsChart() {
    const el = document.querySelector('#mom-chart-analytics');

    if (! el) {
        return null;
    }

    const options = {
        series: [
            {
                name: 'Primary signal',
                type: 'area',
                data: [31, 42, 38, 54, 49, 63, 58, 72, 69, 76, 81, 89],
            },
            {
                name: 'Secondary',
                type: 'line',
                data: [24, 30, 28, 34, 32, 38, 36, 42, 40, 44, 43, 47],
            },
        ],
        chart: {
            height: 300,
            type: 'line',
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'Noto Sans, system-ui, sans-serif',
            background: 'transparent',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 620,
            },
            dropShadow: {
                enabled: true,
                enabledOnSeries: [0],
                top: 2,
                left: 0,
                blur: 12,
                opacity: 0.35,
                color: gold,
            },
        },
        colors: [gold, 'rgba(212,169,95,0.42)'],
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: [2, 2],
            dashArray: [0, 6],
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'vertical',
                shadeIntensity: 0.65,
                opacityFrom: 0.36,
                opacityTo: 0.04,
                stops: [0, 100],
                colorStops: [
                    { offset: 0, color: gold, opacity: 0.4 },
                    { offset: 100, color: '#070707', opacity: 0 },
                ],
            },
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            labels: {
                style: { colors: muted, fontSize: '11px', fontWeight: 500 },
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                style: { colors: muted, fontSize: '11px', fontWeight: 500 },
            },
        },
        grid: {
            borderColor: grid,
            strokeDashArray: 4,
            padding: { top: 4, right: 12, bottom: 0, left: 12 },
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '12px',
            fontWeight: 500,
            labels: { colors: muted },
            markers: {
                strokeWidth: 0,
                fillColors: [gold, 'rgba(212,169,95,0.42)'],
            },
        },
        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '12px',
            },
            x: {
                show: true,
            },
        },
        markers: {
            size: 0,
            hover: {
                size: 4,
                colors: [gold],
                strokeColors: '#1a1410',
                strokeWidth: 2,
            },
        },
    };

    const chart = new ApexCharts(el, options);

    chart.render();

    return chart;
}

/**
 * @returns {import('apexcharts').default | null}
 */
function mountTrafficDonut() {
    const el = document.querySelector('#mom-chart-traffic');

    if (! el) {
        return null;
    }

    const options = {
        series: [42, 26, 18, 14],
        chart: {
            type: 'donut',
            height: 248,
            fontFamily: 'Noto Sans, system-ui, sans-serif',
            background: 'transparent',
            animations: {
                easing: 'easeinout',
                speed: 520,
            },
        },
        labels: ['Organic', 'Direct', 'Social', 'Referral'],
        colors: ['#d4a95f', '#b8894a', '#8e6f42', '#5c4a2a'],
        stroke: {
            show: true,
            width: 2,
            colors: ['#1a1410'],
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        name: { show: false },
                        value: {
                            show: false,
                        },
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'Sessions',
                            fontSize: '11px',
                            fontWeight: 500,
                            color: muted,
                            formatter: () => '24.8k',
                        },
                    },
                },
            },
        },
        dataLabels: { enabled: false },
        legend: {
            position: 'bottom',
            fontSize: '11px',
            fontWeight: 500,
            labels: { colors: muted },
            markers: {
                strokeWidth: 0,
                width: 8,
                height: 8,
            },
            itemMargin: {
                horizontal: 10,
                vertical: 4,
            },
        },
        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '12px',
            },
            y: {
                formatter(val) {
                    return `${val}%`;
                },
            },
        },
    };

    const chart = new ApexCharts(el, options);

    chart.render();

    return chart;
}

document.addEventListener('DOMContentLoaded', () => {
    mountAnalyticsChart();
    mountTrafficDonut();
});
