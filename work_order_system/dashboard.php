<?php
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Get current date ranges
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');
$year_start = date('Y-01-01');

// Get today's statistics
$today_stats_sql = "SELECT 
                        COUNT(*) as today_orders,
                        COALESCE(SUM(total), 0) as today_revenue,
                        COALESCE(AVG(total), 0) as today_avg_order
                    FROM work_orders 
                    WHERE DATE(order_date) = ?";
$today_stmt = $conn->prepare($today_stats_sql);
$today_stmt->bind_param("s", $today);
$today_stmt->execute();
$today_stats = $today_stmt->get_result()->fetch_assoc();
$today_stmt->close();

// Get this week's statistics
$week_stats = get_work_order_stats($conn, $week_start, $today);

// Get this month's statistics
$month_stats = get_work_order_stats($conn, $month_start, $today);

// Get this year's statistics
$year_stats = get_work_order_stats($conn, $year_start, $today);

// Get last 7 days data for trend chart
$last_7_days_sql = "SELECT 
                        DATE(order_date) as date,
                        COUNT(*) as order_count,
                        SUM(total) as revenue
                    FROM work_orders 
                    WHERE order_date >= DATE_SUB(?, INTERVAL 6 DAY)
                    GROUP BY DATE(order_date)
                    ORDER BY date";
$last_7_stmt = $conn->prepare($last_7_days_sql);
$last_7_stmt->bind_param("s", $today);
$last_7_stmt->execute();
$last_7_result = $last_7_stmt->get_result();
$last_7_days = array();
while ($row = $last_7_result->fetch_assoc()) {
    $last_7_days[] = $row;
}
$last_7_stmt->close();

// Get recent work orders (last 10)
$recent_orders_sql = "SELECT 
                          id,
                          work_order_number,
                          contact_name,
                          total,
                          status,
                          order_date,
                          created_at
                      FROM work_orders 
                      ORDER BY created_at DESC 
                      LIMIT 10";
$recent_orders = array();
$recent_result = $conn->query($recent_orders_sql);
if ($recent_result) {
    while ($row = $recent_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Get status distribution for current month
$status_distribution_sql = "SELECT 
                               status,
                               COUNT(*) as count,
                               SUM(total) as total_value
                           FROM work_orders 
                           WHERE order_date >= ?
                           GROUP BY status
                           ORDER BY count DESC";
$status_stmt = $conn->prepare($status_distribution_sql);
$status_stmt->bind_param("s", $month_start);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$status_distribution = array();
while ($row = $status_result->fetch_assoc()) {
    $status_distribution[] = $row;
}
$status_stmt->close();

// Get top customers this month
$top_customers_sql = "SELECT 
                          customer_id,
                          contact_name,
                          COUNT(*) as order_count,
                          SUM(total) as total_spent
                      FROM work_orders 
                      WHERE order_date >= ?
                      GROUP BY customer_id, contact_name
                      ORDER BY total_spent DESC
                      LIMIT 5";
$top_customers_stmt = $conn->prepare($top_customers_sql);
$top_customers_stmt->bind_param("s", $month_start);
$top_customers_stmt->execute();
$top_customers_result = $top_customers_stmt->get_result();
$top_customers = array();
while ($row = $top_customers_result->fetch_assoc()) {
    $top_customers[] = $row;
}
$top_customers_stmt->close();

// Get pending/urgent tasks
$urgent_orders_sql = "SELECT 
                          id,
                          work_order_number,
                          contact_name,
                          total,
                          order_date,
                          DATEDIFF(?, order_date) as days_old
                      FROM work_orders 
                      WHERE status IN ('pending', 'in_progress')
                      AND order_date <= DATE_SUB(?, INTERVAL 3 DAY)
                      ORDER BY order_date ASC
                      LIMIT 5";
$urgent_stmt = $conn->prepare($urgent_orders_sql);
$urgent_stmt->bind_param("ss", $today, $today);
$urgent_stmt->execute();
$urgent_result = $urgent_stmt->get_result();
$urgent_orders = array();
while ($row = $urgent_result->fetch_assoc()) {
    $urgent_orders[] = $row;
}
$urgent_stmt->close();

// Calculate trends (compare with previous periods)
$prev_week_start = date('Y-m-d', strtotime($week_start . ' -7 days'));
$prev_week_end = date('Y-m-d', strtotime($today . ' -7 days'));
$prev_week_stats = get_work_order_stats($conn, $prev_week_start, $prev_week_end);

$revenue_trend = $prev_week_stats['total_amount'] > 0 ? 
    (($week_stats['total_amount'] - $prev_week_stats['total_amount']) / $prev_week_stats['total_amount']) * 100 : 0;
$orders_trend = $prev_week_stats['total_orders'] > 0 ? 
    (($week_stats['total_orders'] - $prev_week_stats['total_orders']) / $prev_week_stats['total_orders']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Work Order Management</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .dashboard-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .metric-card.today {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .metric-card.week {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .metric-card.month {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .metric-card.year {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: scale(0);
            transition: transform 0.6s ease;
        }
        
        .metric-card:hover::before {
            transform: scale(1);
        }
        
        .metric-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 15px;
        }
        
        .metric-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .trend-indicator {
            display: inline-flex;
            align-items: center;
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .quick-action-btn {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .quick-action-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .quick-action-btn.primary {
            background: #667eea;
            color: white;
        }
        
        .quick-action-btn.primary:hover {
            background: #5a67d8;
            color: white;
        }
        
        .recent-order-item {
            padding: 15px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s;
        }
        
        .recent-order-item:hover {
            background-color: #f8f9fa;
        }
        
        .recent-order-item:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            margin: 20px 0;
        }
        
        .chart-container.small {
            height: 200px;
        }
        
        .alert-urgent {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            border: none;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .time-greeting {
            font-size: 1.8rem;
            font-weight: 300;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .weather-widget {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px 0;
            }
            
            .metric-card {
                margin-bottom: 20px;
            }
            
            .welcome-section {
                padding: 20px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="container-fluid">
            <div class="col-12">
                <div class="container-fluid py-4">
                    <!-- Navigation Links -->
                    <div class="nav-links text-center mb-4">
                        <a href="dashboard.php" class="active">dashboard</a>
                        <a href="work_orders.php">work orders</a>
                        <a href="customers.php">customers</a>
                        <a href="index.php">new order</a>
                        <a href="reports.php">reports</a>
                        <a href="settings.php">settings</a>
                    </div>
                    
                    <!-- Welcome Section -->
                    <div class="welcome-section">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="time-greeting" id="timeGreeting">Good Morning!</div>
                                <h1 class="h3 mb-2">Work Order Dashboard</h1>
                                <p class="text-muted mb-3">Welcome back! Here's what's happening with your business today.</p>
                                <div class="quick-actions">
                                    <a href="index.php" class="quick-action-btn primary">
                                        <i class="bi bi-plus-circle me-2"></i>New Work Order
                                    </a>
                                    <a href="work_orders.php" class="quick-action-btn">
                                        <i class="bi bi-list-ul me-2"></i>View All Orders
                                    </a>
                                    <a href="customers.php" class="quick-action-btn">
                                        <i class="bi bi-people me-2"></i>Customers
                                    </a>
                                    <a href="reports.php" class="quick-action-btn">
                                        <i class="bi bi-graph-up me-2"></i>Analytics
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="weather-widget">
                                    <i class="bi bi-sun" style="font-size: 2rem;"></i>
                                    <h5 class="mt-2 mb-1">Today</h5>
                                    <p class="mb-0"><?php echo date('l, F j, Y'); ?></p>
                                    <small><?php echo date('g:i A'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Urgent Alerts -->
                    <?php if (!empty($urgent_orders)): ?>
                    <div class="alert alert-urgent">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                            <div>
                                <h6 class="mb-1">⚠️ Urgent Attention Required</h6>
                                <p class="mb-0"><?php echo count($urgent_orders); ?> work orders are overdue and need immediate attention.</p>
                            </div>
                            <a href="#urgent-section" class="btn btn-light btn-sm ms-auto">View Details</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Key Metrics -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="metric-card today">
                                <i class="bi bi-calendar-day metric-icon"></i>
                                <div class="metric-value">₱<?php echo number_format($today_stats['today_revenue'], 0); ?></div>
                                <div class="metric-label">Today's Revenue</div>
                                <div class="trend-indicator">
                                    <i class="bi bi-receipt me-1"></i>
                                    <?php echo $today_stats['today_orders']; ?> orders
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="metric-card week">
                                <i class="bi bi-calendar-week metric-icon"></i>
                                <div class="metric-value">₱<?php echo number_format($week_stats['total_amount'], 0); ?></div>
                                <div class="metric-label">This Week</div>
                                <div class="trend-indicator">
                                    <i class="bi bi-<?php echo $revenue_trend >= 0 ? 'trending-up' : 'trending-down'; ?> me-1"></i>
                                    <?php echo abs(round($revenue_trend, 1)); ?>% vs last week
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="metric-card month">
                                <i class="bi bi-calendar-month metric-icon"></i>
                                <div class="metric-value">₱<?php echo number_format($month_stats['total_amount'], 0); ?></div>
                                <div class="metric-label">This Month</div>
                                <div class="trend-indicator">
                                    <i class="bi bi-graph-up me-1"></i>
                                    <?php echo $month_stats['total_orders']; ?> orders
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="metric-card year">
                                <i class="bi bi-calendar-range metric-icon"></i>
                                <div class="metric-value">₱<?php echo number_format($year_stats['total_amount'], 0); ?></div>
                                <div class="metric-label">This Year</div>
                                <div class="trend-indicator">
                                    <i class="bi bi-award me-1"></i>
                                    <?php echo number_format($year_stats['average_order_value'], 0); ?> avg
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts and Analytics Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8 mb-4">
                            <div class="dashboard-card">
                                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-4">
                                    <h5 class="card-title mb-0 fw-semibold">📈 7-Day Revenue Trend</h5>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary active" onclick="toggleTrendChart('revenue')">Revenue</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="toggleTrendChart('orders')">Orders</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="trendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-4">
                            <div class="dashboard-card h-100">
                                <div class="card-header bg-transparent border-0 p-4">
                                    <h5 class="card-title mb-0 fw-semibold">📊 Order Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container small">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data Tables Row -->
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="dashboard-card">
                                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-4">
                                    <h5 class="card-title mb-0 fw-semibold">🕒 Recent Work Orders</h5>
                                    <a href="work_orders.php" class="btn btn-outline-primary btn-sm">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recent_orders)): ?>
                                        <div class="text-center py-5">
                                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                            <p class="text-muted mt-3">No work orders yet.</p>
                                            <a href="index.php" class="btn btn-primary">Create First Order</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <div class="recent-order-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($order['work_order_number']); ?></h6>
                                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars($order['contact_name']); ?></p>
                                                        <small class="text-muted"><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></small>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-bold text-success mb-1">₱<?php echo number_format($order['total'], 2); ?></div>
                                                        <span class="status-badge bg-<?php 
                                                            echo $order['status'] === 'completed' ? 'success' : 
                                                                ($order['status'] === 'pending' ? 'warning' : 
                                                                ($order['status'] === 'in_progress' ? 'info' : 'secondary')); 
                                                        ?> text-white">
                                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6 mb-4">
                            <div class="dashboard-card h-100">
                                <div class="card-header bg-transparent border-0 p-4">
                                    <h5 class="card-title mb-0 fw-semibold">🏆 Top Customers (This Month)</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($top_customers)): ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-people" style="font-size: 2rem; color: #ccc;"></i>
                                            <p class="text-muted mt-2">No customer data this month.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($top_customers as $index => $customer): ?>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3">
                                                    <span class="badge bg-<?php echo $index < 2 ? 'warning' : 'secondary'; ?> rounded-pill">
                                                        #<?php echo $index + 1; ?>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($customer['contact_name']); ?></h6>
                                                    <small class="text-muted"><?php echo $customer['order_count']; ?> orders</small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold text-success">₱<?php echo number_format($customer['total_spent'], 0); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Urgent Orders Section -->
                    <?php if (!empty($urgent_orders)): ?>
                    <div class="row" id="urgent-section">
                        <div class="col-12">
                            <div class="dashboard-card">
                                <div class="card-header bg-danger text-white p-4">
                                    <h5 class="card-title mb-0 fw-semibold">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        Urgent: Overdue Work Orders
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php foreach ($urgent_orders as $urgent): ?>
                                        <div class="recent-order-item border-start border-danger border-4">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1 fw-semibold text-danger"><?php echo htmlspecialchars($urgent['work_order_number']); ?></h6>
                                                    <p class="mb-1"><?php echo htmlspecialchars($urgent['contact_name']); ?></p>
                                                    <small class="text-muted">
                                                        Order Date: <?php echo date('M j, Y', strtotime($urgent['order_date'])); ?>
                                                        • <span class="text-danger fw-bold"><?php echo $urgent['days_old']; ?> days overdue</span>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold mb-2">₱<?php echo number_format($urgent['total'], 2); ?></div>
                                                    <button class="btn btn-danger btn-sm" onclick="updateOrderStatus(<?php echo $urgent['id']; ?>, 'in_progress')">
                                                        <i class="bi bi-play-fill"></i> Start Work
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart.js defaults
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.color = '#666';
        
        // Time-based greeting
        function updateTimeGreeting() {
            const hour = new Date().getHours();
            const greetingElement = document.getElementById('timeGreeting');
            
            if (hour < 12) {
                greetingElement.textContent = '🌅 Good Morning!';
            } else if (hour < 17) {
                greetingElement.textContent = '☀️ Good Afternoon!';
            } else {
                greetingElement.textContent = '🌆 Good Evening!';
            }
        }
        
        // Chart data from PHP
        const last7Days = <?php echo json_encode($last_7_days); ?>;
        const statusData = <?php echo json_encode($status_distribution); ?>;
        
        // Initialize charts
        let trendChart, statusChart;
        
        // 7-Day Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: last7Days.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue (₱)',
                    data: last7Days.map(d => d.revenue),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(16, 185, 129)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }, {
                    label: 'Orders',
                    data: last7Days.map(d => d.order_count),
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y1',
                    pointBackgroundColor: 'rgb(102, 126, 234)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    hidden: true
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
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                                } else {
                                    return 'Orders: ' + context.parsed.y;
                                }
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                weight: 500
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1).replace('_', ' ')),
                datasets: [{
                    data: statusData.map(s => s.count),
                    backgroundColor: [
                        '#10b981', // completed - green
                        '#f59e0b', // pending - yellow  
                        '#06b6d4', // in_progress - blue
                        '#6b7280', // draft - gray
                        '#ef4444', // cancelled - red
                        '#8b5cf6'  // billed - purple
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
        
        // Toggle trend chart data
        function toggleTrendChart(type) {
            // Update button states
            document.querySelectorAll('.btn-group button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show/hide datasets
            if (type === 'revenue') {
                trendChart.data.datasets[0].hidden = false;
                trendChart.data.datasets[1].hidden = true;
            } else {
                trendChart.data.datasets[0].hidden = true;
                trendChart.data.datasets[1].hidden = false;
            }
            trendChart.update();
        }
        
        // Update order status function
        function updateOrderStatus(orderId, newStatus) {
            if (confirm(`Change order status to "${newStatus.replace('_', ' ')}"?`)) {
                fetch('update_work_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        work_order_id: orderId,
                        status: newStatus,
                        comment: 'Status updated from dashboard'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status');
                });
            }
        }
        
        // Auto-refresh dashboard every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
        
        // Initialize greeting
        updateTimeGreeting();
        
        // Add loading animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.metric-card, .dashboard-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Real-time clock update
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            
            const clockElements = document.querySelectorAll('small');
            clockElements.forEach(el => {
                if (el.textContent.includes('M')) {
                    el.textContent = timeString;
                }
            });
        }
        
        // Update clock every minute
        setInterval(updateClock, 60000);
    </script>
</body>
</html>