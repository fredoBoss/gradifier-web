<!DOCTYPE html>
<html>

<head>
    <title>Pie Chart of Class Weights</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <h2>Total Weight per Class</h2>
    <div style="display: flex; justify-content: center; align-items: center; height: 100%;">
        <canvas id="classPieChart" style="max-width: 600px; max-height: 600px;"></canvas>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <script>
        fetch('DashBackend.php')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById("classPieChart").getContext("2d");

                new Chart(ctx, {
                    type: "pie",
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.weights,
                            backgroundColor: [
                                "#0000FF", // 25BCP
                                "#008000", // 30BCP
                                "#FF0000", // 33BCP
                                "#E4E6C9", // 30TR
                                "#FFE5B4", // IF36TR
                                "#000000" // IF38TR
                            ],
                            borderWidth: 0,
                            hoverOffset: 10,
                        }]
                    },
                    options: {
                        layout: {
                            padding: {
                                right: 0
                            }
                        },
                        plugins: {
                            legend: {
                                position: "right",
                                labels: {
                                    font: {
                                        size: 14
                                    },
                                    padding: 7, // reduce space between legend items and chart
                                    boxWidth: 25, // optional: adjust legend box size
                                    boxHeight: 15,
                                    generateLabels: (chart) => {
                                        const dataset = chart.data.datasets[0];
                                        return chart.data.labels.map((label, i) => ({
                                            text: `${label}: ${dataset.data[i]} kg`,
                                            fillStyle: dataset.backgroundColor[i],
                                            pointStyle: "circle",
                                            radius: 6,
                                            hidden: isNaN(dataset.data[i]),
                                            index: i,
                                        }));
                                    },
                                },
                                usePointStyle: true,
                            },
                            datalabels: {
                                formatter: (value) => `${value} kg`,
                                color: "#000",
                                anchor: "end",
                                align: "start",
                                offset: 10,
                                font: {
                                    size: 12,
                                },
                            },
                            title: {
                                display: true,
                                text: "Total Weight by Finger Class"
                            }
                        },
                        responsive: true,
                        maintainAspectRatio: false,
                    },
                    plugins: [ChartDataLabels],
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
    </script>

    <!-- <script>
        fetch('DashBackend.php')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('classPieChart').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Total Weight',
                            data: data.weights,
                            backgroundColor: [
                                '#FF6384', '#36A2EB', '#FFCE56',
                                '#4BC0C0', '#9966FF', '#FF9F40'
                            ],
                            borderColor: '#fff',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            title: {
                                display: true,
                                text: 'Total Weight by Finger Class'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
    </script> -->
</body>

</html>