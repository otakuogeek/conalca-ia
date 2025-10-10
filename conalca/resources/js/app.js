import Chart from "chart.js/auto";

(async function () {
    const data = [
        { month: "ENE", count: 110 },
        { month: "FEB", count: 80 },
        { month: "MAR", count: 160 },
        { month: "ABR", count: 120 },
        { month: "MAY", count: 80 },
        { month: "JUN", count: 60 },
        { month: "JUL", count: 120 },
        { month: "AGO", count: 80 },
        { month: "SEP", count: 10 },
        { month: "OCT", count: 70 },
        { month: "NOV", count: 90 },
        { month: "DIC", count: 200 },
    ];

    new Chart(document.getElementById("acquisitions"), {
        type: "bar",
        options: {
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return `$${context.parsed.y}k`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        callback: function (value) {
                            return `$${value}k`;
                        },
                    },
                },
            },
            elements: {
                bar: {
                    borderRadius: 5,
                },
            },
        },
        data: {
            labels: data.map((row) => row.month),
            datasets: [
                {
                    type: "line",
                    borderColor: "#FF7C32",
                    data: data.map((row) => row.count),
                },
                {
                    backgroundColor: "#FCECD6",
                    data: data.map((row) => row.count),
                },
            ],
        },
    });
})();
