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
    <title>Work Orders List</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Main Content -->
            <div class="col-12">
                <div class="container-fluid py-4">
                    <!-- Navigation Links -->
                    <div class="nav-links text-center mb-4">
                        <a href="dashboard.php">dashboard</a>
                        <a href="work_orders.php" class="active">work orders</a>
                        <a href="customers.php">customers</a>
                        <a href="index.php">new order</a>
                        <a href="reports.php">reports</a>
                        <a href="settings.php">settings</a>
                    </div>
                    
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Work Orders</h1>
                        <a href="index.php" class="btn btn-primary">
                            <i class="bi bi-plus"></i> New Work Order
                        </a>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><?php echo $stats['total_orders']; ?></h5>
                                    <p class="card-text">Total Orders</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-warning"><?php echo $stats['pending_orders']; ?></h5>
                                    <p class="card-text">Pending</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-info"><?php echo $stats['in_progress_orders']; ?></h5>
                                    <p class="card-text">In Progress</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-success">₱<?php echo number_format($stats['total_amount'], 0); ?></h5>
                                    <p class="card-text">Total Value</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search and Filter -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Search by work order #, customer name, or email..." 
                                           value="<?php echo htmlspecialchars($search_term); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-control" name="status">
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
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Work Orders Table -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($work_orders)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <h5 class="mt-3">No work orders found</h5>
                                    <p>No work orders match your search criteria.</p>
                                    <a href="index.php" class="btn btn-primary">Create First Work Order</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Work Order #</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Status</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($work_orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($order['work_order_number']); ?></strong>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($order['contact_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($order['contact_email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $order['status'] === 'completed' ? 'success' : 
                                                                ($order['status'] === 'pending' ? 'warning' : 
                                                                ($order['status'] === 'in_progress' ? 'info' : 'secondary')); 
                                                        ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $order['item_count']; ?> items</td>
                                                    <td><strong>₱<?php echo number_format($order['total'], 2); ?></strong></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-primary" onclick="viewWorkOrder(<?php echo $order['id']; ?>)">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary" onclick="printWorkOrder(<?php echo $order['id']; ?>)">
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
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function viewWorkOrder(id) {
            window.open(`print_work_order.php?id=${id}`, '_blank');
        }
        
        function printWorkOrder(id) {
            window.open(`print_work_order.php?id=${id}`, '_blank');
        }
    </script>
</body>
</html>