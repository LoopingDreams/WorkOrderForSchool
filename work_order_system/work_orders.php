<?php
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Get search parameters
$search_term = sanitize_input($_GET['search'] ?? '');
$status_filter = sanitize_input($_GET['status'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get work orders
$work_orders = search_work_orders($conn, $search_term, $status_filter, $limit, $offset);

// Get statistics
$stats = get_work_order_stats($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Orders - Modern Dashboard</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" as="style">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* CSS Custom Properties for theming */
        :root {
            --primary-color: #6366f1;
            --primary-hover: #5855eb;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #475569;
        }

        * {
            font-family: var(--font-family);
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .modern-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .modern-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-secondary) 100%);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .dark-mode-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1050;
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 50px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .dark-mode-toggle:hover {
            box-shadow: var(--shadow-lg);
            transform: scale(1.05);
        }

        .toggle-switch {
            width: 44px;
            height: 24px;
            background: var(--border-color);
            border-radius: 12px;
            position: relative;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: var(--primary-color);
        }

        .toggle-slider {
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(20px);
        }

        .modern-table {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .modern-table thead {
            background: var(--bg-secondary);
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: var(--bg-secondary);
        }

        .modern-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .modern-btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            padding: 8px 16px;
        }

        .modern-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .search-container {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .modern-input {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 16px;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .modern-input:focus {
            background: var(--bg-primary);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .nav-links {
            background: var(--bg-card);
            border-radius: 50px;
            padding: 8px;
            display: inline-flex;
            gap: 4px;
            box-shadow: var(--shadow);
            margin: 0 auto 2rem auto;
        }

        .nav-links a {
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-links a:hover, .nav-links a.active {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Stagger animation delays */
        .stats-card:nth-child(1) .fade-in { animation-delay: 0.1s; }
        .stats-card:nth-child(2) .fade-in { animation-delay: 0.2s; }
        .stats-card:nth-child(3) .fade-in { animation-delay: 0.3s; }
        .stats-card:nth-child(4) .fade-in { animation-delay: 0.4s; }

        tbody tr:nth-child(n) { animation-delay: calc(0.1s * var(--row-index)); }
    </style>
</head>
<body>
    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" id="darkModeToggle">
        <i class="bi bi-sun-fill" id="themeIcon"></i>
        <div class="toggle-switch" id="toggleSwitch">
            <div class="toggle-slider"></div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container-fluid py-4" style="margin-left: 80px;">
        <!-- Navigation -->
        <div class="d-flex justify-content-center mb-4">
            <div class="nav-links fade-in">
                <a href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="work_orders.php" class="active">
                    <i class="bi bi-clipboard-data me-2"></i>Work Orders
                </a>
                <a href="customers.php">
                    <i class="bi bi-people me-2"></i>Customers
                </a>
                <a href="index.php">
                    <i class="bi bi-plus-circle me-2"></i>New Order
                </a>
                <a href="reports.php">
                    <i class="bi bi-graph-up me-2"></i>Reports
                </a>
                <a href="settings.php">
                    <i class="bi bi-gear me-2"></i>Settings
                </a>
            </div>
        </div>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
            <div>
                <h1 class="h2 mb-1 fw-bold">Work Orders</h1>
                <p class="text-muted mb-0">Manage and track all your work orders</p>
            </div>
            <a href="index.php" class="btn btn-primary modern-btn">
                <i class="bi bi-plus me-2"></i>
                New Work Order
            </a>
        </div>
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="fade-in">
                        <div class="stats-number text-primary">
                            <i class="bi bi-clipboard2-data me-2"></i>
                            <?php echo $stats['total_orders']; ?>
                        </div>
                        <div class="stats-label">Total Orders</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="fade-in">
                        <div class="stats-number text-warning">
                            <i class="bi bi-clock me-2"></i>
                            <?php echo $stats['pending_orders']; ?>
                        </div>
                        <div class="stats-label">Pending</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="fade-in">
                        <div class="stats-number text-info">
                            <i class="bi bi-arrow-repeat me-2"></i>
                            <?php echo $stats['in_progress_orders']; ?>
                        </div>
                        <div class="stats-label">In Progress</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="fade-in">
                        <div class="stats-number text-success">
                            <i class="bi bi-currency-dollar me-2"></i>
                            ₱<?php echo number_format($stats['total_amount'], 0); ?>
                        </div>
                        <div class="stats-label">Total Value</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div class="search-container mb-4 fade-in">
            <form method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Search</label>
                        <input 
                            type="text" 
                            class="form-control modern-input" 
                            name="search"
                            placeholder="Work order #, customer name, or email..." 
                            value="<?php echo htmlspecialchars($search_term); ?>"
                        >
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-control modern-input" name="status">
                            <option value="">All Statuses</option>
                            <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="billed" <?php echo $status_filter === 'billed' ? 'selected' : ''; ?>>Billed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary modern-btn w-100">
                            <i class="bi bi-search me-2"></i>
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Work Orders Table -->
        <?php if (empty($work_orders)): ?>
            <div class="modern-card p-5 text-center fade-in">
                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted);"></i>
                <h5 class="mt-3">No work orders found</h5>
                <p class="text-muted">No work orders match your search criteria.</p>
                <a href="index.php" class="btn btn-primary modern-btn">
                    <i class="bi bi-plus me-2"></i>
                    Create First Work Order
                </a>
            </div>
        <?php else: ?>
            <div class="modern-table fade-in">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="fw-semibold">Work Order #</th>
                                <th class="fw-semibold">Date</th>
                                <th class="fw-semibold">Customer</th>
                                <th class="fw-semibold">Status</th>
                                <th class="fw-semibold">Items</th>
                                <th class="fw-semibold">Total</th>
                                <th class="fw-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($work_orders as $index => $order): ?>
                                <tr class="fade-in" style="--row-index: <?php echo $index + 1; ?>;">
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($order['work_order_number']); ?></strong>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['contact_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['contact_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status_config = [
                                            'completed' => ['color' => 'success', 'icon' => 'check-circle'],
                                            'pending' => ['color' => 'warning', 'icon' => 'clock'],
                                            'in_progress' => ['color' => 'info', 'icon' => 'arrow-repeat'],
                                            'draft' => ['color' => 'secondary', 'icon' => 'pencil'],
                                            'cancelled' => ['color' => 'danger', 'icon' => 'x-circle'],
                                            'billed' => ['color' => 'primary', 'icon' => 'receipt']
                                        ];
                                        $config = $status_config[$order['status']] ?? $status_config['draft'];
                                        ?>
                                        <span class="modern-badge bg-<?php echo $config['color']; ?> text-white">
                                            <i class="bi bi-<?php echo $config['icon']; ?> me-1"></i>
                                            <?php echo strtoupper(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo $order['item_count']; ?> items
                                        </span>
                                    </td>
                                    <td>
                                        <strong>₱<?php echo number_format($order['total'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button 
                                                type="button" 
                                                class="btn btn-outline-primary modern-btn"
                                                onclick="viewWorkOrder(<?php echo $order['id']; ?>)"
                                                title="View Details"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button 
                                                type="button" 
                                                class="btn btn-outline-secondary modern-btn"
                                                onclick="printWorkOrder(<?php echo $order['id']; ?>)"
                                                title="Print"
                                            >
                                                <i class="bi bi-printer"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if (count($work_orders) >= $limit): ?>
                <nav aria-label="Work orders pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_term); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="page-item active">
                            <span class="page-link"><?php echo $page; ?></span>
                        </li>
                        
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_term); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Fast-loading JavaScript (no external dependencies) -->
    <script>
        // Dark mode functionality
        (function() {
            const toggle = document.getElementById('darkModeToggle');
            const toggleSwitch = document.getElementById('toggleSwitch');
            const themeIcon = document.getElementById('themeIcon');
            
            // Get saved theme or system preference
            const savedTheme = localStorage.getItem('darkMode');
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = savedTheme === 'true' || (savedTheme === null && systemDark);
            
            // Apply theme immediately (prevent flash)
            function applyTheme(dark) {
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
                toggleSwitch.classList.toggle('active', dark);
                themeIcon.className = dark ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
                localStorage.setItem('darkMode', dark);
            }
            
            // Apply initial theme
            applyTheme(isDark);
            
            // Toggle theme on click
            toggle.addEventListener('click', () => {
                const currentlyDark = document.documentElement.getAttribute('data-theme') === 'dark';
                applyTheme(!currentlyDark);
            });
        })();

        // Work order functions
        function viewWorkOrder(id) {
            window.open(`print_work_order.php?id=${id}`, '_blank');
        }
        
        function printWorkOrder(id) {
            window.open(`print_work_order.php?id=${id}`, '_blank');
        }
        
        // Trigger animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add staggered animation to table rows
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>

    <!-- Bootstrap JS (defer loading) -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>