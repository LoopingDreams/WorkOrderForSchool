<?php
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Get date filters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today
$report_type = $_GET['report_type'] ?? 'overview';

// Get statistics for the period
$stats = get_work_order_stats($conn, $date_from, $date_to);

// Get monthly data for trends (last 12 months)
$monthly_sql = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    MONTHNAME(order_date) as month_name,
                    YEAR(order_date) as year,
                    COUNT(*) as order_count,
                    SUM(total) as revenue,
                    AVG(total) as avg_order_value,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_orders
                FROM work_orders 
                WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m'), MONTHNAME(order_date), YEAR(order_date)
                ORDER BY month";

$monthly_result = $conn->query($monthly_sql);
$monthly_data = array();
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
}

// Get daily data for current month
$daily_sql = "SELECT 
                  DATE(order_date) as date,
                  DAY(order_date) as day,
                  COUNT(*) as order_count,
                  SUM(total) as revenue
              FROM work_orders 
              WHERE order_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
              GROUP BY DATE(order_date), DAY(order_date)
              ORDER BY date";

$daily_result = $conn->query($daily_sql);
$daily_data = array();
while ($row = $daily_result->fetch_assoc()) {
    $daily_data[] = $row;
}

// Get status breakdown with details
$status_sql = "SELECT 
                   status,
                   COUNT(*) as count,
                   SUM(total) as total_value,
                   AVG(total) as avg_value,
                   MIN(total) as min_value,
                   MAX(total) as max_value
               FROM work_orders 
               WHERE order_date BETWEEN ? AND ?
               GROUP BY status
               ORDER BY count DESC";

$status_stmt = $conn->prepare($status_sql);
$status_stmt->bind_param("ss", $date_from, $date_to);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$status_data = array();
while ($row = $status_result->fetch_assoc()) {
    $status_data[] = $row;
}
$status_stmt->close();

// Get customer analysis
$customer_analysis_sql = "SELECT 
                             customer_id,
                             contact_name,
                             contact_email,
                             COUNT(*) as order_count,
                             SUM(total) as total_spent,
                             AVG(total) as avg_order_value,
                             MIN(order_date) as first_order,
                             MAX(order_date) as last_order,
                             DATEDIFF(MAX(order_date), MIN(order_date)) as customer_lifetime_days
                         FROM work_orders 
                         WHERE order_date BETWEEN ? AND ?
                         GROUP BY customer_id, contact_name, contact_email
                         ORDER BY total_spent DESC
                         LIMIT 15";

$customer_stmt = $conn->prepare($customer_analysis_sql);
$customer_stmt->bind_param("ss", $date_from, $date_to);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer_analysis = array();
while ($row = $customer_result->fetch_assoc()) {
    $customer_analysis[] = $row;
}
$customer_stmt->close();

// Get revenue by hour of day
$hourly_sql = "SELECT 
                   HOUR(created_at) as hour,
                   COUNT(*) as order_count,
                   SUM(total) as revenue
               FROM work_orders 
               WHERE order_date BETWEEN ? AND ?
               GROUP BY HOUR(created_at)
               ORDER BY hour";

$hourly_stmt = $conn->prepare($hourly_sql);
$hourly_stmt->bind_param("ss", $date_from, $date_to);
$hourly_stmt->execute();
$hourly_result = $hourly_stmt->get_result();
$hourly_data = array();
for ($i = 0; $i < 24; $i++) {
    $hourly_data[$i] = ['hour' => $i, 'order_count' => 0, 'revenue' => 0];
}
while ($row = $hourly_result->fetch_assoc()) {
    $hourly_data[$row['hour']] = $row;
}
$hourly_stmt->close();

// Get order value distribution
$value_distribution_sql = "SELECT 
                              CASE 
                                  WHEN total < 1000 THEN 'Under ₱1,000'
                                  WHEN total < 5000 THEN '₱1,000 - ₱5,000'
                                  WHEN total < 10000 THEN '₱5,000 - ₱10,000'
                                  WHEN total < 25000 THEN '₱10,000 - ₱25,000'
                                  WHEN total < 50000 THEN '₱25,000 - ₱50,000'
                                  ELSE 'Over ₱50,000'
                              END as value_range,
                              COUNT(*) as count,
                              SUM(total) as total_value
                          FROM work_orders 
                          WHERE order_date BETWEEN ? AND ?
                          GROUP BY 
                              CASE 
                                  WHEN total < 1000 THEN 'Under ₱1,000'
                                  WHEN total < 5000 THEN '₱1,000 - ₱5,000'
                                  WHEN total < 10000 THEN '₱5,000 - ₱10,000'
                                  WHEN total < 25000 THEN '₱10,000 - ₱25,000'
                                  WHEN total < 50000 THEN '₱25,000 - ₱50,000'
                                  ELSE 'Over ₱50,000'
                              END
                          ORDER BY MIN(total)";

$value_stmt = $conn->prepare($value_distribution_sql);
$value_stmt->bind_param("ss", $date_from, $date_to);
$value_stmt->execute();
$value_result = $value_stmt->get_result();
$value_distribution = array();
while ($row = $value_result->fetch_assoc()) {
    $value_distribution[] = $row;
}
$value_stmt->close();

// Calculate KPIs
$total_revenue = $stats['total_amount'];
$total_orders = $stats['total_orders'];
$avg_order_value = $stats['average_order_value'];
$completion_rate = $total_orders > 0 ? ($stats['completed_orders'] / $total_orders) * 100 : 0;

// Previous period comparison (same number of days, previous period)
$period_days = (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24) + 1;
$prev_date_to = date('Y-m-d', strtotime($date_from . ' -1 day'));
$prev_date_from = date('Y-m-d', strtotime($prev_date_to . ' -' . ($period_days - 1) . ' days'));
$prev_stats = get_work_order_stats($conn, $prev_date_from, $prev_date_to);

// Calculate growth rates
$revenue_growth = $prev_stats['total_amount'] > 0 ? (($total_revenue - $prev_stats['total_amount']) / $prev_stats['total_amount']) * 100 : 0;
$orders_growth = $prev_stats['total_orders'] > 0 ? (($total_orders - $prev_stats['total_orders']) / $prev_stats['total_orders']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
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
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    
    <style>
        .analytics-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .analytics-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card.revenue {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .metric-card.orders {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .metric-card.avg-value {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .metric-card.completion {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        
        .growth-indicator {
            display: inline-flex;
            align-items: center;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .growth-positive {
            color: #10b981;
        }
        
        .growth-negative {
            color: #ef4444;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        .chart-container.large {
            height: 400px;
        }
        
        .dashboard-tabs .nav-link {
            border-radius: 25px;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .dashboard-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .table-modern {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .table-modern thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .export-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            color: white;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        
        .export-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="container-fluid py-4">
                    <!-- Navigation Links -->
                    <div class="nav-links text-center mb-4">
                        <a href="dashboard.php">dashboard</a>
                        <a href="work_orders.php">work orders</a>
                        <a href="customers.php">customers</a>
                        <a href="index.php">new order</a>
                        <a href="reports.php" class="active">reports</a>
                        <a href="settings.php">settings</a>
                    </div>
                    
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">📊 Analytics Dashboard</h1>
                            <p class="text-muted mb-0">Comprehensive business insights and performance metrics</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn export-btn" onclick="exportReport()">
                                <i class="bi bi-download"></i> Export Report
                            </button>
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                    
                    <!-- Date Filter & Controls -->
                    <div class="analytics-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label fw-semibold">From Date</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label fw-semibold">To Date</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="report_type" class="form-label fw-semibold">View</label>
                                    <select class="form-control" id="report_type" name="report_type">
                                        <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                                        <option value="detailed" <?php echo $report_type === 'detailed' ? 'selected' : ''; ?>>Detailed Analysis</option>
                                        <option value="trends" <?php echo $report_type === 'trends' ? 'selected' : ''; ?>>Trends & Forecasting</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-graph-up"></i> Update Analytics
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Key Performance Indicators -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="metric-card revenue">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="fw-bold mb-1">₱<?php echo number_format($total_revenue, 0); ?></h3>
                                        <p class="mb-2 opacity-90">Total Revenue</p>
                                        <div class="growth-indicator <?php echo $revenue_growth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                            <i class="bi bi-<?php echo $revenue_growth >= 0 ? 'trending-up' : 'trending-down'; ?> me-1"></i>
                                            <?php echo abs(round($revenue_growth, 1)); ?>% vs prev period
                                        </div>
                                    </div>
                                    <i class="bi bi-currency-dollar" style="font-size: 2rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-card orders">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="fw-bold mb-1"><?php echo number_format($total_orders); ?></h3>
                                        <p class="mb-2 opacity-90">Total Orders</p>
                                        <div class="growth-indicator <?php echo $orders_growth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                            <i class="bi bi-<?php echo $orders_growth >= 0 ? 'trending-up' : 'trending-down'; ?> me-1"></i>
                                            <?php echo abs(round($orders_growth, 1)); ?>% vs prev period
                                        </div>
                                    </div>
                                    <i class="bi bi-receipt" style="font-size: 2rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-card avg-value">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="fw-bold mb-1">₱<?php echo number_format($avg_order_value, 0); ?></h3>
                                        <p class="mb-2 opacity-90">Avg Order Value</p>
                                        <div class="growth-indicator growth-positive">
                                            <i class="bi bi-graph-up me-1"></i>
                                            Target: ₱15,000
                                        </div>
                                    </div>
                                    <i class="bi bi-bar-chart" style="font-size: 2rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-card completion">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="fw-bold mb-1"><?php echo round($completion_rate, 1); ?>%</h3>
                                        <p class="mb-2 opacity-90">Completion Rate</p>
                                        <div class="growth-indicator growth-positive">
                                            <i class="bi bi-check-circle me-1"></i>
                                            <?php echo $stats['completed_orders']; ?> of <?php echo $total_orders; ?> orders
                                        </div>
                                    </div>
                                    <i class="bi bi-speedometer2" style="font-size: 2rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dashboard Tabs -->
                    <ul class="nav nav-pills dashboard-tabs mb-4" id="dashboardTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="trends-tab" data-bs-toggle="pill" data-bs-target="#trends" type="button" role="tab">
                                📈 Revenue Trends
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="analysis-tab" data-bs-toggle="pill" data-bs-target="#analysis" type="button" role="tab">
                                📊 Order Analysis
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="customers-tab" data-bs-toggle="pill" data-bs-target="#customers" type="button" role="tab">
                                👥 Customer Insights
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="performance-tab" data-bs-toggle="pill" data-bs-target="#performance" type="button" role="tab">
                                ⚡ Performance
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="dashboardTabsContent">
                        
                        <!-- Revenue Trends Tab -->
                        <div class="tab-pane fade show active" id="trends" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8 mb-4">
                                    <div class="analytics-card">
                                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0 fw-semibold">📈 Monthly Revenue & Order Trends</h5>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary active" onclick="toggleChartData('revenue')">Revenue</button>
                                                <button type="button" class="btn btn-outline-primary" onclick="toggleChartData('orders')">Orders</button>
                                                <button type="button" class="btn btn-outline-primary" onclick="toggleChartData('both')">Both</button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container large">
                                                <canvas id="trendsChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-4">
                                    <div class="analytics-card h-100">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="card-title mb-0 fw-semibold">📅 Daily Performance</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="dailyChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Analysis Tab -->
                        <div class="tab-pane fade" id="analysis" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="analytics-card">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="card-title mb-0 fw-semibold">📊 Order Status Distribution</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="statusChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="analytics-card">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="card-title mb-0 fw-semibold">💰 Order Value Distribution</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="valueChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="analytics-card">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="card-title mb-0 fw-semibold">📈 Hourly Order Pattern</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="hourlyChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Insights Tab -->
                        <div class="tab-pane fade" id="customers" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8 mb-4">
                                    <div class="analytics-card">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="card-title mb-0 fw-semibold">🏆 Top Customers by Revenue</h5>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($customer_analysis)): ?>
                                                <div class="text-center py-4">
                                                    <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
                                                    <p class="text-muted mt-3">No customer data available for the selected period.</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-modern">
                                                        <thead>
                                                            <tr>
                                                                <th>Rank</th>
                                                                <th>Customer</th>
                                                                <th>Orders</th>
                                                                <th>Total Spent</th>
                                                                <th>Avg Order</th>
                                                                <th>Customer Since</th>
                                                                <th>Lifetime Value</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($customer_analysis as $index => $customer): ?>
                                                                <tr>
                                                                    <td>
                                                                        <span class="badge bg-<?php echo $index < 3 ? 'warning' : 'secondary'; ?>">
                                                                            #<?php echo $index + 1; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <strong><?php echo htmlspecialchars($customer['contact_name']); ?></strong>
                                                                            <br><small class="text-muted"><?php echo htmlspecialchars($customer['customer_id']); ?></small>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-primary"><?php echo $customer['order_count']; ?></span>
                                                                    </td>
                                                                    <td>
                                                                        <strong class="text-success">₱<?php echo number_format($customer['total_spent'], 2); ?></strong>
                                                                    </td>
                                                                    <td>₱<?php echo number_format($customer['avg_order_value'], 2); ?></td>
                                                                    <td><?php echo date('M Y', strtotime($customer['first_order'])); ?></td>
                                                                    <td>
                                                                        <div class="progress" style="height: 6px;">
                                                                            <div class="progress-bar bg-success" style="width: <?php echo min(100, ($customer['total_spent'] / max(array_column($customer_analysis, 'total_spent'))) * 100); ?>%"></div>
                                                                        </div>
                                                                        <small class="text-muted"><?php echo $customer['customer_lifetime_days']; ?> days</small>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-4">
                                    <div class="analytics-card h-100">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="card-title mb-0 fw-semibold">🎯 Customer Metrics</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row text-center">
                                                <div class="col-12 mb-3">
                                                    <h4 class="text-primary mb-1"><?php echo count($customer_analysis); ?></h4>
                                                    <small class="text-muted">Active Customers</small>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <h5 class="text-success mb-1"><?php echo $total_orders > 0 ? round($total_orders / max(1, count($customer_analysis)), 1) : 0; ?></h5>
                                                    <small class="text-muted">Avg Orders/Customer</small>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <h5 class="text-info mb-1">₱<?php echo count($customer_analysis) > 0 ? number_format($total_revenue / count($customer_analysis), 0) : 0; ?></h5>
                                                    <small class="text-muted">Avg Spend/Customer</small>
                                                </div>
                                            </div>
                                            
                                            <hr>
                                            
                                            <h6 class="fw-semibold mb-3">Customer Segmentation</h6>
                                            <?php
                                            $high_value = 0;
                                            $medium_value = 0;
                                            $low_value = 0;
                                            
                                            foreach ($customer_analysis as $customer) {
                                                if ($customer['total_spent'] >= 50000) $high_value++;
                                                elseif ($customer['total_spent'] >= 10000) $medium_value++;
                                                else $low_value++;
                                            }
                                            ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between">
                                                    <small>High Value (₱50K+)</small>
                                                    <small><strong><?php echo $high_value; ?></strong></small>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo count($customer_analysis) > 0 ? ($high_value / count($customer_analysis)) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between">
                                                    <small>Medium Value (₱10K-50K)</small>
                                                    <small><strong><?php echo $medium_value; ?></strong></small>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-warning" style="width: <?php echo count($customer_analysis) > 0 ? ($medium_value / count($customer_analysis)) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between">
                                                    <small>Low Value (Under ₱10K)</small>
                                                    <small><strong><?php echo $low_value; ?></strong></small>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-info" style="width: <?php echo count($customer_analysis) > 0 ? ($low_value / count($customer_analysis)) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Performance Tab -->
                        <div class="tab-pane fade" id="performance" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-12 mb-4">
                                    <div class="analytics-card">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="card-title mb-0 fw-semibold">⚡ Performance Summary</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-8">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6 class="fw-semibold text-primary">📊 Order Processing</h6>
                                                            <ul class="list-unstyled">
                                                                <li class="mb-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <span>Completion Rate</span>
                                                                        <strong class="text-success"><?php echo round($completion_rate, 1); ?>%</strong>
                                                                    </div>
                                                                </li>
                                                                <li class="mb-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <span>Pending Orders</span>
                                                                        <strong class="text-warning"><?php echo $stats['pending_orders']; ?></strong>
                                                                    </div>
                                                                </li>
                                                                <li class="mb-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <span>In Progress</span>
                                                                        <strong class="text-info"><?php echo $stats['in_progress_orders']; ?></strong>
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="fw-semibold text-success">💰 Revenue Metrics</h6>
                                                            <ul class="list-unstyled">
                                                                <li class="mb-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <span>Revenue Growth</span>
                                                                        <strong class="<?php echo $revenue_growth >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                                            <?php echo ($revenue_growth >= 0 ? '+' : '') . round($revenue_growth, 1); ?>%
                                                                        </strong>
                                                                    </div>
                                                                </li>
                                                                <li class="mb-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <span>Order Growth</span>
                                                                        <strong class="<?php echo $orders_growth >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                                            <?php echo ($orders_growth >= 0 ? '+' : '') . round($orders_growth, 1); ?>%
                                                                        </strong>
                                                                    </div>
                                                                </li>
                                                                <li class="mb-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <span>Daily Avg Revenue</span>
                                                                        <strong class="text-primary">₱<?php echo number_format($total_revenue / max(1, $period_days), 0); ?></strong>
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="text-center">
                                                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width: 100px; height: 100px;">
                                                            <i class="bi bi-trophy text-primary" style="font-size: 2.5rem;"></i>
                                                        </div>
                                                        <h6 class="mt-3 mb-2">Overall Performance</h6>
                                                        <h3 class="text-primary fw-bold">
                                                            <?php 
                                                            $overall_score = ($completion_rate + ($revenue_growth > 0 ? 20 : 0) + ($orders_growth > 0 ? 20 : 0)) / 3;
                                                            echo round($overall_score, 0); 
                                                            ?>%
                                                        </h3>
                                                        <small class="text-muted">Performance Score</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart.js default configuration
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.color = '#666';
        Chart.defaults.scale.grid.color = 'rgba(0,0,0,0.05)';
        
        // Color palette
        const colors = {
            primary: '#667eea',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#06b6d4',
            gradient: {
                primary: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                success: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                warning: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
            }
        };
        
        // Chart data
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        const dailyData = <?php echo json_encode($daily_data); ?>;
        const statusData = <?php echo json_encode($status_data); ?>;
        const valueDistribution = <?php echo json_encode($value_distribution); ?>;
        const hourlyData = <?php echo json_encode(array_values($hourly_data)); ?>;
        
        // Initialize charts
        let trendsChart, dailyChart, statusChart, valueChart, hourlyChart;
        
        // Revenue Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month_name + ' ' + d.year),
                datasets: [{
                    label: 'Revenue (₱)',
                    data: monthlyData.map(d => d.revenue),
                    borderColor: colors.success,
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.success,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }, {
                    label: 'Orders',
                    data: monthlyData.map(d => d.order_count),
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y1',
                    pointBackgroundColor: colors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₱)'
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
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });
        
        // Daily Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: dailyData.map(d => 'Day ' + d.day),
                datasets: [{
                    label: 'Daily Revenue',
                    data: dailyData.map(d => d.revenue),
                    backgroundColor: colors.primary,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1).replace('_', ' ')),
                datasets: [{
                    data: statusData.map(s => s.count),
                    backgroundColor: [
                        colors.success,   // completed
                        colors.warning,   // pending
                        colors.info,      // in_progress
                        '#6c757d',        // draft
                        colors.danger,    // cancelled
                        '#343a40'         // billed
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
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
        
        // Value Distribution Chart
        const valueCtx = document.getElementById('valueChart').getContext('2d');
        valueChart = new Chart(valueCtx, {
            type: 'polarArea',
            data: {
                labels: valueDistribution.map(v => v.value_range),
                datasets: [{
                    data: valueDistribution.map(v => v.count),
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(6, 182, 212, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
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
                            usePointStyle: true
                        }
                    }
                }
            }
        });
        
        // Hourly Pattern Chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        hourlyChart = new Chart(hourlyCtx, {
            type: 'line',
            data: {
                labels: hourlyData.map(h => h.hour + ':00'),
                datasets: [{
                    label: 'Orders per Hour',
                    data: hourlyData.map(h => h.order_count),
                    borderColor: colors.info,
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.info,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hour of Day'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        }
                    }
                }
            }
        });
        
        // Toggle chart data function
        function toggleChartData(type) {
            // Update button states
            document.querySelectorAll('.btn-group button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update chart visibility
            if (type === 'revenue') {
                trendsChart.data.datasets[0].hidden = false;
                trendsChart.data.datasets[1].hidden = true;
            } else if (type === 'orders') {
                trendsChart.data.datasets[0].hidden = true;
                trendsChart.data.datasets[1].hidden = false;
            } else {
                trendsChart.data.datasets[0].hidden = false;
                trendsChart.data.datasets[1].hidden = false;
            }
            trendsChart.update();
        }
        
        // Export report function
        function exportReport() {
            const reportData = {
                period: {
                    from: '<?php echo $date_from; ?>',
                    to: '<?php echo $date_to; ?>'
                },
                summary: {
                    totalRevenue: <?php echo $total_revenue; ?>,
                    totalOrders: <?php echo $total_orders; ?>,
                    avgOrderValue: <?php echo $avg_order_value; ?>,
                    completionRate: <?php echo $completion_rate; ?>,
                    revenueGrowth: <?php echo $revenue_growth; ?>,
                    ordersGrowth: <?php echo $orders_growth; ?>
                },
                monthlyData: monthlyData,
                statusData: statusData,
                topCustomers: <?php echo json_encode(array_slice($customer_analysis, 0, 10)); ?>
            };
            
            // Create and download JSON file
            const dataStr = JSON.stringify(reportData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `work_order_report_${reportData.period.from}_to_${reportData.period.to}.json`;
            link.click();
            URL.revokeObjectURL(url);
        }
        
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate metric cards on load
            const metricCards = document.querySelectorAll('.metric-card');
            metricCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 150);
            });
        });
    </script>
    
    <style>
        @media print {
            .nav-links, .btn, .export-btn, .dashboard-tabs {
                display: none !important;
            }
            
            .analytics-card {
                break-inside: avoid;
                margin-bottom: 20px;
            }
            
            .chart-container {
                height: 250px !important;
            }
        }
    </style>
</body>
</html>