<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
//     header("location: /pages/login.php");
//     exit;
// }

require_once "../assets/db_connection.php";

$requiredTables = ['achievements', 'challenges', 'leaderboards'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        $missingTables[] = $table;
    }
}

$pageTitle = "Admin Dashboard";
$bodyClass = "admin-page dark-mode"; 

$additionalHead = '<style>
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1rem;
}
.stat-icon.purple { background-color: rgba(124, 77, 255, 0.1); color: #7c4dff; }
.stat-icon.green { background-color: rgba(54, 179, 126, 0.1); color: #36b37e; }
.stat-icon.blue { background-color: rgba(51, 102, 255, 0.1); color: #3366ff; }
.stat-icon.orange { background-color: rgba(255, 171, 0, 0.1); color: #ffab00; }
.stat-icon.red { background-color: rgba(255, 86, 48, 0.1); color: #ff5630; }
.stat-icon.teal { background-color: rgba(0, 184, 217, 0.1); color: #00b8d9; }
.health-indicator { 
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 6px;
}
.health-indicator.healthy { background-color: #36b37e; }
.health-indicator.warning { background-color: #ffab00; }
.health-indicator.critical { background-color: #ff5630; }
.highlight-card {
    border-left: 4px solid #7c4dff;
}
.metrics-container {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}
.metric-card {
    background-color: var(--card-bg);
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
    flex: 1;
    min-width: 150px;
    transition: none !important;
    transform: none !important;
}
.metric-card:hover {
    transform: none !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
    background-color: var(--card-bg) !important;
}
.chart-card, .stats-card {
    transition: none !important;
    transform: none !important;
}
.chart-card:hover, .stats-card:hover {
    transform: none !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
    background-color: var(--card-bg) !important;
}
.metric-card .metric-value {
    font-size: 1.75rem;
    font-weight: 600;
    margin: 0.5rem 0;
}
.metric-card .metric-label {
    color: var(--sidebar-icon);
    font-size: 0.875rem;
}
.chart-row {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.chart-card {
    background-color: var(--card-bg);
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    flex: 1;
    min-width: 300px;
    overflow: hidden;
}
.dashboard-cards {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.stats-card {
    background-color: var(--card-bg);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    flex: 1;
    min-width: 300px;
}
.revenue-section {
    display: flex;
    flex-direction: row;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.revenue-chart {
    flex: 7;
    min-width: 300px;
}
.revenue-table {
    flex: 5;
    min-width: 300px;
}
.table {
    color: var(--text-color);
}
.table th {
    border-color: rgba(255, 255, 255, 0.1);
}
.table td {
    border-color: rgba(255, 255, 255, 0.05);
}
.trend-up {
    color: #36b37e;
}
.trend-down {
    color: #ff5630;
}
@media (max-width: 768px) {
    .revenue-section {
        flex-direction: column;
    }
    .chart-row {
        flex-direction: column;
    }
}
</style>';


require_once "includes/sidebar.php";
?>

<div class="dashboard-header">
    <div class="dashboard-title">
        <h1>Dashboard Overview</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
    </div>
    <div>
        <select class="form-control custom-select" id="time-period">
            <option value="today">Today</option>
            <option value="week" selected>This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
        </select>
    </div>
</div>
<div class="dashboard-cards">
    <div class="stats-card">
        <div class="stats-card-header">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
                <h3>ACTIVE USERS</h3>
        </div>
        <div class="stats-card-value">1,245</div>
            <div class="stats-card-trend trend-up">
                <i class="fas fa-arrow-up trend-icon"></i> +16% than last week
            </div>
        <div class="stats-card-chart">
            <canvas class="sparkline-canvas" height="50"></canvas>
        </div>
    </div>
        
     <div class="stats-card">
        <div class="stats-card-header">
            <div class="stat-icon green">
                <i class="fas fa-dollar-sign"></i>
            </div>
                <h3>WEEKLY REVENUE</h3>
        </div>
        <div class="stats-card-value">$8,632</div>
        <div class="stats-card-trend trend-up">
            <i class="fas fa-arrow-up trend-icon"></i> +8% than last week
        </div>
        <div class="stats-card-chart">
            <canvas class="sparkline-canvas" height="50"></canvas>
        </div>
    </div>
    <div class="stats-card">
        <div class="stats-card-header">
            <div class="stat-icon orange">
                <i class="fas fa-dumbbell"></i>
            </div>
            <h3>WORKOUTS COMPLETED</h3>
        </div>
        <div class="stats-card-value">5,879</div>
            <div class="stats-card-trend trend-up">
                <i class="fas fa-arrow-up trend-icon"></i> +12% than last week
            </div>
        <div class="stats-card-chart">
            <canvas class="sparkline-canvas" height="50"></canvas>
        </div>
    </div>
</div>

<div class="chart-card highlight-card" style="margin-bottom: 1.5rem;">
    <div class="chart-header">
        <h3><i class="fas fa-store"></i> MARKETPLACE PERFORMANCE</h3>
        <div class="chart-actions">
            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-ellipsis-v"></i></button>
        </div>
                </div>
    <div class="chart-body">
        <div class="metrics-container">
            <div style="flex: 8;">
                <canvas id="marketplaceChart" height="250"></canvas>
            </div>
            <div style="flex: 4; display: flex; flex-wrap: wrap; gap: 1rem;">
                <div class="metric-card">
                    <div class="metric-label">Products</div>
                    <div class="metric-value">312</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Sellers</div>
                    <div class="metric-value">48</div>
                    </div>
                <div class="metric-card">
                    <div class="metric-label">Orders</div>
                    <div class="metric-value">253</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Conversion</div>
                    <div class="metric-value">3.2%</div>
                </div>
            </div>
        </div>
    </div>
            </div>
            
<div class="chart-row">
    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-chart-line"></i> USER ENGAGEMENT</h3>
                    </div>
        <div class="chart-body">
            <canvas id="engagementChart" height="250"></canvas>
        </div>
            </div>
            
    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-trophy"></i> ACHIEVEMENTS & CHALLENGES</h3>
        </div>
        <div class="chart-body">
            <div class="row">
                <div class="col-md-6">
                    <div style="text-align: center; padding: 1rem;">
                        <h4 style="font-size: 1rem; margin-bottom: 1rem;">Active Challenges</h4>
                        <div class="big-number">12</div>
                        <div class="chart-trend trend-up">
                            <i class="fas fa-arrow-up"></i> 4 new this week
            </div>
                    </div>
                    </div>
                <div class="col-md-6">
                    <div style="text-align: center; padding: 1rem;">
                        <h4 style="font-size: 1rem; margin-bottom: 1rem;">Achievements Unlocked</h4>
                        <div class="big-number">874</div>
                        <div class="chart-trend trend-up">
                            <i class="fas fa-arrow-up"></i> 142 this week
                    </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h4 style="font-size: 0.9rem; color: var(--sidebar-icon); margin-bottom: 0.75rem;">Top Challenges</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>30-Day Strength Challenge</td>
                                    <td>247 participants</td>
                                </tr>
                                <tr>
                                    <td>Summer Beach Body</td>
                                    <td>189 participants</td>
                                </tr>
                                <tr>
                                    <td>HIIT Cardio Mastery</td>
                                    <td>136 participants</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                    </div>
                    </div>
                    </div>
                </div>
                
<div class="chart-row">
    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-dumbbell"></i> FITNESS CONTENT</h3>
                    </div>
        <div class="chart-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="workoutTypesChart" height="200"></canvas>
                </div>
                <div class="col-md-6">
                    <h4 style="font-size: 0.9rem; color: var(--sidebar-icon); margin-bottom: 0.75rem;">Top Workouts</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Full Body HIIT</td>
                                    <td>842 completions</td>
                                </tr>
                                <tr>
                                    <td>Core Strength Builder</td>
                                    <td>756 completions</td>
                                </tr>
                                <tr>
                                    <td>5K Runner Program</td>
                                    <td>589 completions</td>
                                </tr>
                                <tr>
                                    <td>Upper Body Power</td>
                                    <td>472 completions</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>
        </div>
        
    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-apple-alt"></i> NUTRITION ANALYTICS</h3>
            </div>
        <div class="chart-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="nutritionChart" height="200"></canvas>
                        </div>
                <div class="col-md-6">
                    <h4 style="font-size: 0.9rem; color: var(--sidebar-icon); margin-bottom: 0.75rem;">Popular Nutrition Plans</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Lean Muscle Builder</td>
                                    <td>324 active users</td>
                                </tr>
                                <tr>
                                    <td>Balanced Macros</td>
                                    <td>287 active users</td>
                                </tr>
                                <tr>
                                    <td>Weight Loss Essentials</td>
                                    <td>253 active users</td>
                                </tr>
                                <tr>
                                    <td>Clean Eating Kickstart</td>
                                    <td>189 active users</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>

<div class="chart-card" style="margin-top: 1.5rem;">
    <div class="chart-header">
        <h3><i class="fas fa-money-bill-wave"></i> REVENUE BREAKDOWN</h3>
        <div class="date-range">
            LAST 30 DAYS <i class="fas fa-caret-down"></i>
        </div>
    </div>
    <div class="chart-body">
        <div class="revenue-section">
            <div class="revenue-chart">
                <canvas id="revenueChart" height="250"></canvas>
            </div>
            <div class="revenue-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Amount</th>
                                <th>Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-box text-primary"></i> Product Sales</td>
                                <td>$12,845</td>
                                <td><span class="trend-up">+12%</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-tshirt text-success"></i> Merchandise</td>
                                <td>$8,320</td>
                                <td><span class="trend-up">+24%</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-user-shield text-info"></i> Trainers</td>
                                <td>$5,670</td>
                                <td><span class="trend-up">+8%</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-file-alt text-warning"></i> Premium Content</td>
                                <td>$3,450</td>
                                <td><span class="trend-down">-3%</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-ad text-danger"></i> Advertising</td>
                                <td>$1,975</td>
                                <td><span class="trend-up">+5%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</div> 
</div> 
</div> 

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

<script src="/assets/js/admin-sidebar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 800,
        once: true
    });
    
    const userData = [980, 1020, 1050, 1080, 1150, 1190, 1245];
    const revenueData = [7200, 7400, 7900, 8100, 8300, 8500, 8632];
    const workoutData = [4800, 5100, 5300, 5450, 5600, 5750, 5879];
    
    drawSparkline(document.querySelectorAll('.sparkline-canvas')[0], userData, '#3366ff');
    drawSparkline(document.querySelectorAll('.sparkline-canvas')[1], revenueData, '#36b37e');
    drawSparkline(document.querySelectorAll('.sparkline-canvas')[2], workoutData, '#ffab00');
    
    const marketplaceCtx = document.getElementById('marketplaceChart').getContext('2d');
    new Chart(marketplaceCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [
                {
                    label: 'Orders',
                    data: [120, 150, 180, 210, 190, 230, 250],
                    borderColor: '#7c4dff',
                    backgroundColor: 'rgba(124, 77, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Revenue',
                    data: [8000, 10000, 12000, 13500, 12800, 14500, 16000],
                    borderColor: '#36b37e',
                    backgroundColor: 'rgba(54, 179, 126, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    const engagementCtx = document.getElementById('engagementChart').getContext('2d');
    new Chart(engagementCtx, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Active Users',
                    data: [850, 750, 820, 780, 890, 1200, 980],
                    backgroundColor: '#3366ff',
                    borderRadius: 4,
                },
                {
                    label: 'Workout Sessions',
                    data: [320, 280, 310, 290, 340, 420, 380],
                    backgroundColor: '#ffab00',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    const workoutTypesCtx = document.getElementById('workoutTypesChart').getContext('2d');
    new Chart(workoutTypesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Strength', 'Cardio', 'HIIT', 'Yoga', 'Other'],
            datasets: [{
                data: [35, 25, 20, 15, 5],
                backgroundColor: ['#3366ff', '#36b37e', '#ffab00', '#7c4dff', '#00b8d9'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            },
            cutout: '65%'
        }
    });
    
    const nutritionCtx = document.getElementById('nutritionChart').getContext('2d');
    new Chart(nutritionCtx, {
        type: 'pie',
        data: {
            labels: ['Weight Loss', 'Muscle Gain', 'Balanced', 'Specialized'],
            datasets: [{
                data: [40, 30, 20, 10],
                backgroundColor: ['#ff5630', '#ffab00', '#36b37e', '#00b8d9'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
    
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [
                {
                    label: 'Product Sales',
                    data: [8000, 9500, 11000, 10500, 12000, 11500, 12845],
                    backgroundColor: '#3366ff',
                    borderRadius: 4,
                    stack: 'stack1'
                },
                {
                    label: 'Merchandise',
                    data: [4500, 5000, 6000, 6500, 7000, 7800, 8320],
                    backgroundColor: '#36b37e',
                    borderRadius: 4,
                    stack: 'stack1'
                },
                {
                    label: 'Trainers',
                    data: [3000, 3500, 4000, 4500, 5000, 5200, 5670],
                    backgroundColor: '#00b8d9',
                    borderRadius: 4,
                    stack: 'stack1'
                },
                {
                    label: 'Premium Content',
                    data: [2500, 3000, 3500, 3800, 3600, 3500, 3450],
                    backgroundColor: '#ffab00',
                    borderRadius: 4,
                    stack: 'stack1'
                },
                {
                    label: 'Advertising',
                    data: [1200, 1300, 1500, 1600, 1700, 1800, 1975],
                    backgroundColor: '#ff5630',
                    borderRadius: 4,
                    stack: 'stack1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    stacked: true,
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});

function drawSparkline(canvas, data, color) {
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    gradient.addColorStop(0, color + '30');
    gradient.addColorStop(1, color + '05');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: Array(data.length).fill(''),
            datasets: [{
                data: data,
                borderColor: color,
                borderWidth: 2,
                backgroundColor: gradient,
                pointRadius: 0,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            },
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false
                }
            }
        }
    });
}
</script>
</body>
</html> 