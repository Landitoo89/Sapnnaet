document.addEventListener('DOMContentLoaded', () => {
    let myChart = null; // Variable para almacenar la instancia del Chart

    // Function to load chart data based on view type (monthly/yearly)
    function loadChartData(viewType) {
        // Construct the URL with the viewType parameter
        const apiUrl = `api/estadisticas.php?type=${viewType}`;
        const chartTitleElement = document.getElementById('chartTitle');
        const ctx = document.getElementById('workersChart').getContext('2d');

        // Destroy previous chart instance if it exists
        if (myChart) {
            myChart.destroy();
        }

        fetch(apiUrl)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                return response.json();
            })
            .then(data => {
                // Update the chart title
                chartTitleElement.textContent = data.title;

                // Configure x-axis ticks based on viewType
                let xTicksOptions = {
                    maxRotation: 45,
                    minRotation: 45,
                    font: { size: 12 }
                };

                // For daily view, reduce rotation and potentially increase font size if needed
                if (viewType === 'monthly') { // 'monthly' now means daily view
                    xTicksOptions.maxRotation = 90; // More rotation for more labels
                    xTicksOptions.minRotation = 90;
                    xTicksOptions.font.size = 10; // Smaller font for many labels
                } else if (viewType === 'yearly') { // 'yearly' now means monthly view
                    xTicksOptions.maxRotation = 45;
                    xTicksOptions.minRotation = 45;
                    xTicksOptions.font.size = 12;
                }

                // Create the new chart
                myChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels, // Dynamic labels from PHP
                        datasets: [
                            {
                                label: 'Activos',
                                data: data.datasets.activos,
                                backgroundColor: '#2ecc71',
                                borderColor: '#27ae60',
                                borderWidth: 1
                            },
                            {
                                label: 'Vacaciones',
                                data: data.datasets.vacaciones,
                                backgroundColor: '#e67e22',
                                borderColor: '#d35400',
                                borderWidth: 1
                            },
                            {
                                label: 'Cumpleaños',
                                data: data.datasets.cumpleanos,
                                backgroundColor: '#9b59b6',
                                borderColor: '#8e44ad',
                                borderWidth: 1
                            },
                            {
                                label: 'Reposos',
                                data: data.datasets.reposos,
                                backgroundColor: '#3498db',
                                borderColor: '#2980b9',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 14,
                                        family: 'Arial'
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: data.title, // Use dynamic title from PHP
                                font: {
                                    size: 18,
                                    weight: 'bold'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Cantidad de Trabajadores',
                                    font: { size: 14 }
                                },
                                grid: { color: 'rgba(0,0,0,0.05)' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: xTicksOptions // Apply dynamic tick options
                            }
                        },
                        animation: {
                            duration: 1500,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('workersChart').innerHTML = `
                    <div class="alert alert-danger">
                        Error al cargar los datos: ${error.message}
                    </div>
                `;
            });
    }

    // Event listeners for view buttons
    document.getElementById('view_monthly').addEventListener('change', () => {
        loadChartData('monthly'); // 'monthly' now means daily view for current month
    });

    document.getElementById('view_yearly').addEventListener('change', () => {
        loadChartData('yearly'); // 'yearly' now means monthly view for the year
    });

    // Initial load when the DOM is ready
    // This will load the 'yearly' view (which now displays by month) by default
    loadChartData('yearly');
});
