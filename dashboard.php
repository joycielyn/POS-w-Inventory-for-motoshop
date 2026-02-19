<?php
include_once'connectdb.php';
session_start();

if($_SESSION['useremail']==""){
    header('location:../index.php');
}

include_once"header.php";

// ================= SALES OVERVIEW STATISTICS =================

// Today's Sales
$today_sales = 0;
$today_transactions = 0;
try {
    $select_today = $pdo->prepare("SELECT SUM(total) as total, COUNT(*) as transactions FROM tbl_invoice WHERE DATE(order_date) = CURDATE()");
    $select_today->execute();
    $today_result = $select_today->fetch(PDO::FETCH_ASSOC);
    $today_sales = $today_result['total'] ? $today_result['total'] : 0;
    $today_transactions = $today_result['transactions'] ? $today_result['transactions'] : 0;
} catch(Exception $e) {
    $today_sales = 0;
    $today_transactions = 0;
}

// This Week Sales
$week_sales = 0;
try {
    $select_week = $pdo->prepare("SELECT SUM(total) as total FROM tbl_invoice WHERE YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)");
    $select_week->execute();
    $week_result = $select_week->fetch(PDO::FETCH_ASSOC);
    $week_sales = $week_result['total'] ? $week_result['total'] : 0;
} catch(Exception $e) {
    $week_sales = 0;
}

// This Month Sales
$month_sales = 0;
try {
    $select_month = $pdo->prepare("SELECT SUM(total) as total FROM tbl_invoice WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())");
    $select_month->execute();
    $month_result = $select_month->fetch(PDO::FETCH_ASSOC);
    $month_sales = $month_result['total'] ? $month_result['total'] : 0;
} catch(Exception $e) {
    $month_sales = 0;
}

// Total Profit (Sale Price - Purchase Price)
$total_profit = 0;
try {
    $select_profit = $pdo->prepare("
        SELECT SUM((invoice_detail.saleprice - product.purchaseprice) * invoice_detail.qty) as profit
        FROM tbl_invoice_details invoice_detail
        INNER JOIN tbl_product product ON invoice_detail.product_id = product.pid
    ");
    $select_profit->execute();
    $profit_result = $select_profit->fetch(PDO::FETCH_ASSOC);
    $total_profit = $profit_result['profit'] ? $profit_result['profit'] : 0;
} catch(Exception $e) {
    $total_profit = 0;
}

// Average Sales per Transaction
$avg_sales = $today_transactions > 0 ? ($today_sales / $today_transactions) : 0;

// ================= INVENTORY STATISTICS =================

// Total Products
$select_products = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_product");
$select_products->execute();
$total_products = $select_products->fetch(PDO::FETCH_ASSOC)['total'];

// Low Stock Products (stock <= 10)
$select_low_stock = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_product WHERE stock <= 10 AND stock > 0");
$select_low_stock->execute();
$low_stock_products = $select_low_stock->fetch(PDO::FETCH_ASSOC)['total'];

// Out of Stock Products
$select_out_stock = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_product WHERE stock = 0");
$select_out_stock->execute();
$out_stock_products = $select_out_stock->fetch(PDO::FETCH_ASSOC)['total'];

// Newly Added Products (last 7 days)
$newly_added = 0;
try {
    $select_new = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_product WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $select_new->execute();
    $newly_added = $select_new->fetch(PDO::FETCH_ASSOC)['total'];
} catch(Exception $e) {
    $newly_added = 0;
}

// Total Inventory Value (based on purchase price)
$select_inventory_value = $pdo->prepare("SELECT SUM(stock * purchaseprice) as total FROM tbl_product");
$select_inventory_value->execute();
$inventory_value = $select_inventory_value->fetch(PDO::FETCH_ASSOC)['total'];
$inventory_value = $inventory_value ? $inventory_value : 0;

// Total Categories
$select_categories = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_category");
$select_categories->execute();
$total_categories = $select_categories->fetch(PDO::FETCH_ASSOC)['total'];

// ================= USER MANAGEMENT STATISTICS =================

// Total Users
$select_users = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_user");
$select_users->execute();
$total_users = $select_users->fetch(PDO::FETCH_ASSOC)['total'];

// ================= FINANCIAL SUMMARY =================

// Total Revenue
$total_revenue = 0;
try {
    $select_revenue = $pdo->prepare("SELECT SUM(total) as total FROM tbl_invoice");
    $select_revenue->execute();
    $revenue_result = $select_revenue->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $revenue_result['total'] ? $revenue_result['total'] : 0;
} catch(Exception $e) {
    $total_revenue = 0;
}

// Calculate Net Income (simplified: revenue - inventory cost)
$net_income = $total_revenue - $inventory_value;

// Cash on Hand (you can customize this based on your system)
$cash_on_hand = $total_revenue * 0.3; // Example: 30% of revenue as cash

?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Main Dashboard Cards -->
            <div class="row">
                
                <!-- ================= 1. SALES OVERVIEW CARD ================= -->
                <div class="col-lg-4 col-md-6">
                    <div class="card card-primary card-outline shadow dashboard-card" data-toggle="modal" data-target="#salesOverviewModal" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-4x mb-3 text-primary"></i>
                            <h4><strong>Sales Overview</strong></h4>
                            <p class="text-muted">View sales statistics and performance</p>
                            <span class="badge badge-primary badge-pill px-3 py-2">Click to View Details</span>
                        </div>
                    </div>
                </div>


                <!-- ================= 2. SALES ANALYTICS CARD ================= -->
                <div class="col-lg-4 col-md-6">
                    <div class="card card-success card-outline shadow dashboard-card" data-toggle="modal" data-target="#salesAnalyticsModal" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-pie fa-4x mb-3 text-success"></i>
                            <h4><strong>Sales Analytics</strong></h4>
                            <p class="text-muted">Charts and visual reports</p>
                            <span class="badge badge-success badge-pill px-3 py-2">Click to View Details</span>
                        </div>
                    </div>
                </div>

                <!-- ================= 3. INVENTORY SUMMARY CARD ================= -->
                <div class="col-lg-4 col-md-6">
                    <div class="card card-info card-outline shadow dashboard-card" data-toggle="modal" data-target="#inventoryModal" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <i class="fas fa-boxes fa-4x mb-3 text-info"></i>
                            <h4><strong>Inventory Summary</strong></h4>
                            <p class="text-muted">Stock status and alerts</p>
                            <span class="badge badge-info badge-pill px-3 py-2">Click to View Details</span>
                        </div>
                    </div>
                </div>

                <!-- ================= 4. USER MANAGEMENT CARD ================= -->
                <div class="col-lg-4 col-md-6">
                    <div class="card card-warning card-outline shadow dashboard-card" data-toggle="modal" data-target="#userManagementModal" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-4x mb-3 text-warning"></i>
                            <h4><strong>User Management</strong></h4>
                            <p class="text-muted">Staff and user information</p>
                            <span class="badge badge-warning badge-pill px-3 py-2">Click to View Details</span>
                        </div>
                    </div>
                </div>

                <!-- ================= 5. FINANCIAL SUMMARY CARD ================= -->
                <div class="col-lg-4 col-md-6">
                    <div class="card card-danger card-outline shadow dashboard-card" data-toggle="modal" data-target="#financialModal" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <i class="fas fa-money-check-alt fa-4x mb-3 text-danger"></i>
                            <h4><strong>Financial Summary</strong></h4>
                            <p class="text-muted">Revenue and expenses</p>
                            <span class="badge badge-danger badge-pill px-3 py-2">Click to View Details</span>
                        </div>
                    </div>
                </div>

                <!-- ================= 6. QUICK ACCESS CARD ================= -->
                <div class="col-lg-4 col-md-6">
                    <div class="card card-secondary card-outline shadow dashboard-card" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <i class="fas fa-th-large fa-4x mb-3 text-secondary"></i>
                            <h4><strong>Quick Access</strong></h4>
                            <p class="text-muted">Navigate to main modules</p>
                            <div class="row mt-3">
                                <div class="col-6 mb-2">
                                    <a href="pos.php" class="btn btn-sm btn-primary btn-block">
                                        <i class="fas fa-cash-register"></i> POS
                                    </a>
                                </div>
                                <div class="col-6 mb-2">
                                    <a href="productlist.php" class="btn btn-sm btn-info btn-block">
                                        <i class="fas fa-box"></i> Products
                                    </a>
                                </div>
                                <div class="col-6 mb-2">
                                    <a href="orderlist.php" class="btn btn-sm btn-success btn-block">
                                        <i class="fas fa-list"></i> Orders
                                    </a>
                                </div>
                                <div class="col-6 mb-2">
                                    <a href="category.php" class="btn btn-sm btn-warning btn-block">
                                        <i class="fas fa-tags"></i> Category
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>
</div>

<!-- ================= MODAL 1: SALES OVERVIEW ================= -->
<div class="modal fade" id="salesOverviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-chart-line"></i> Sales Overview</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Total Sales Today -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>₱<?php echo number_format($today_sales, 2); ?></h3>
                                <p><i class="fas fa-calendar-day"></i> Sales Today</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Sales This Week -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>₱<?php echo number_format($week_sales, 2); ?></h3>
                                <p><i class="fas fa-calendar-week"></i> Sales This Week</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Sales This Month -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>₱<?php echo number_format($month_sales, 2); ?></h3>
                                <p><i class="fas fa-calendar-alt"></i> Sales This Month</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Number of Transactions -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?php echo number_format($today_transactions); ?></h3>
                                <p><i class="fas fa-receipt"></i> Transactions Today</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Total Profit -->
                    <div class="col-lg-6 col-12">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Profit</span>
                                <span class="info-box-number">₱<?php echo number_format($total_profit, 2); ?></span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">Overall profit from sales</span>
                            </div>
                        </div>
                    </div>

                    <!-- Average Sales per Transaction -->
                    <div class="col-lg-6 col-12">
                        <div class="info-box bg-gradient-primary">
                            <span class="info-box-icon"><i class="fas fa-chart-bar"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Average per Transaction</span>
                                <span class="info-box-number">₱<?php echo number_format($avg_sales, 2); ?></span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">Today's average sale amount</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="orderlist.php" class="btn btn-primary"><i class="fas fa-list"></i> View All Orders</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL 2: SALES ANALYTICS ================= -->
<div class="modal fade" id="salesAnalyticsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white"><i class="fas fa-chart-pie"></i> Sales Analytics & Charts</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Daily Sales Chart -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h5 class="card-title text-white m-0"><i class="fas fa-chart-line"></i> Daily Sales (Last 7 Days)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="dailySalesChart" style="height: 250px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Selling Products -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-success">
                                <h5 class="card-title text-white m-0"><i class="fas fa-trophy"></i> Top Selling Products</h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped m-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Qty Sold</th>
                                            <th class="text-right">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $select_top = $pdo->prepare("
                                                SELECT p.product, SUM(id.qty) as total_qty, SUM(id.qty * id.saleprice) as revenue
                                                FROM tbl_invoice_details id
                                                INNER JOIN tbl_product p ON id.product_id = p.pid
                                                GROUP BY id.product_id
                                                ORDER BY total_qty DESC
                                                LIMIT 5
                                            ");
                                            $select_top->execute();
                                            if($select_top->rowCount() > 0){
                                                while($row = $select_top->fetch(PDO::FETCH_ASSOC)){
                                                    echo '<tr>
                                                        <td><strong>'.htmlspecialchars($row['product']).'</strong></td>
                                                        <td class="text-center"><span class="badge badge-primary">'.$row['total_qty'].'</span></td>
                                                        <td class="text-right"><strong>₱'.number_format($row['revenue'], 2).'</strong></td>
                                                    </tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="3" class="text-center text-muted">No sales data available</td></tr>';
                                            }
                                        } catch(Exception $e) {
                                            echo '<tr><td colspan="3" class="text-center text-muted">No sales data available</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <!-- Sales by Category -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="card-title text-white m-0"><i class="fas fa-layer-group"></i> Sales by Category</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart" style="height: 250px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Sales Graph -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-info">
                                <h5 class="card-title text-white m-0"><i class="fas fa-chart-area"></i> Monthly Sales (This Year)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlySalesChart" style="height: 250px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL 3: INVENTORY SUMMARY ================= -->
<div class="modal fade" id="inventoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-boxes"></i> Inventory Summary</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body"
                <div class="row">
                    <!-- Total Products -->
                    <div class="col-lg-3 col-6">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-box"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Products</span>
                                <span class="info-box-number"><?php echo number_format($total_products); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Items -->
                    <div class="col-lg-3 col-6">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Low Stock Items</span>
                                <span class="info-box-number"><?php echo number_format($low_stock_products); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Out of Stock Items -->
                    <div class="col-lg-3 col-6">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-ban"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Out of Stock</span>
                                <span class="info-box-number"><?php echo number_format($out_stock_products); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Newly Added Products -->
                    <div class="col-lg-3 col-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-plus-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">New (7 days)</span>
                                <span class="info-box-number"><?php echo number_format($newly_added); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Inventory Value -->
                    <div class="col-lg-6">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-warehouse"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Inventory Value</span>
                                <span class="info-box-number">₱<?php echo number_format($inventory_value, 2); ?></span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">Based on purchase price</span>
                            </div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="col-lg-6">
                        <div class="info-box bg-gradient-secondary">
                            <span class="info-box-icon"><i class="fas fa-tags"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Categories</span>
                                <span class="info-box-number"><?php echo number_format($total_categories); ?></span>
                                <div class="progress">
                                    <div class="progress-bar bg-secondary" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">Product categories</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="productlist.php" class="btn btn-info"><i class="fas fa-box"></i> View Products</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL 4: USER MANAGEMENT ================= -->
<div class="modal fade" id="userManagementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white"><i class="fas fa-users"></i> User / Staff Management</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Total Users -->
                    <div class="col-lg-4 col-12">
                        <div class="small-box bg-gradient-primary">
                            <div class="inner">
                                <h3><?php echo number_format($total_users); ?></h3>
                                <p><i class="fas fa-user-friends"></i> Total Users</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Add Cashier/Admin -->
                    <div class="col-lg-4 col-12">
                        <div class="small-box bg-gradient-success">
                            <div class="inner">
                                <h3><i class="fas fa-user-plus"></i></h3>
                                <p><i class="fas fa-plus-square"></i> Add New User</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                    </div>

                    <!-- User Activity -->
                    <div class="col-lg-4 col-12">
                        <div class="small-box bg-gradient-info">
                            <div class="inner">
                                <h3><i class="fas fa-history"></i></h3>
                                <p><i class="fas fa-clipboard-list"></i> Activity Logs</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="registration.php" class="btn btn-warning"><i class="fas fa-users"></i> Manage Users</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL 5: FINANCIAL SUMMARY ================= -->
<div class="modal fade" id="financialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="fas fa-money-check-alt"></i> Financial Summary</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Total Revenue -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>₱<?php echo number_format($total_revenue, 2); ?></h3>
                                <p><i class="fas fa-chart-line"></i> Total Revenue</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Expenses (Inventory Cost) -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>₱<?php echo number_format($inventory_value, 2); ?></h3>
                                <p><i class="fas fa-file-invoice-dollar"></i> Inventory Cost</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Net Income -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>₱<?php echo number_format($net_income, 2); ?></h3>
                                <p><i class="fas fa-coins"></i> Net Income</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Cash on Hand -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>₱<?php echo number_format($cash_on_hand, 2); ?></h3>
                                <p><i class="fas fa-wallet"></i> Cash on Hand</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<?php include_once"footer.php"; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
/* Dashboard Custom Styling */
.content-wrapper {
    background-color: #f4f6f9;
}

.dashboard-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.dashboard-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important;
    border-color: rgba(0,0,0,0.1);
}

.dashboard-card .card-body {
    padding: 30px;
}

.dashboard-card i.fa-4x {
    transition: transform 0.3s ease;
}

.dashboard-card:hover i.fa-4x {
    transform: scale(1.1);
}

.small-box {
    border-radius: 10px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.small-box .inner {
    padding: 15px;
}

.small-box .inner h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.small-box .inner p {
    font-size: 0.9rem;
    font-weight: 500;
}

.small-box .icon {
    font-size: 70px;
    opacity: 0.3;
}

.card {
    border-radius: 10px;
    border: none;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
    padding: 15px 20px;
}

.card-title {
    font-size: 1.1rem;
    font-weight: 600;
}

.shadow {
    box-shadow: 0 0 1rem rgba(0,0,0,.15) !important;
}

.info-box {
    border-radius: 8px;
    margin-bottom: 15px;
    min-height: 120px;
}

.info-box-icon {
    border-radius: 8px 0 0 8px;
}

.info-box-text {
    font-weight: 600;
    font-size: 0.85rem;
}

.info-box-number {
    font-weight: 700;
    font-size: 1.3rem;
}

.progress-description {
    font-size: 0.8rem;
}

/* Modal Styling */
.modal-dialog {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    border-radius: 10px 10px 0 0;
}

.modal-content {
    border-radius: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .small-box .inner h3 {
        font-size: 1.3rem;
    }
    
    .small-box .inner p {
        font-size: 0.8rem;
    }
    
    .small-box .icon {
        font-size: 50px;
    }
    
    .dashboard-card .card-body {
        padding: 20px;
    }
    
    .dashboard-card i.fa-4x {
        font-size: 3rem;
    }
}
</style>

<script>
$(document).ready(function() {
    // Add animation on load
    $('.small-box, .info-box, .card').hide().each(function(index) {
        $(this).delay(50 * index).fadeIn(400);
    });
    
    // ================= DAILY SALES CHART (Last 7 Days) =================
    <?php
    $daily_labels = [];
    $daily_data = [];
    try {
        for($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $label = date('M d', strtotime("-$i days"));
            $daily_labels[] = $label;
            
            $select_daily = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total FROM tbl_invoice WHERE DATE(order_date) = :date");
            $select_daily->bindParam(':date', $date);
            $select_daily->execute();
            $result = $select_daily->fetch(PDO::FETCH_ASSOC);
            $daily_data[] = $result['total'] ? $result['total'] : 0;
        }
    } catch(Exception $e) {
        for($i = 6; $i >= 0; $i--) {
            $label = date('M d', strtotime("-$i days"));
            $daily_labels[] = $label;
            $daily_data[] = 0;
        }
    }
    ?>
    
    var ctx1 = document.getElementById('dailySalesChart').getContext('2d');
    var dailySalesChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($daily_labels); ?>,
            datasets: [{
                label: 'Daily Sales (₱)',
                data: <?php echo json_encode($daily_data); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
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
    
    // ================= SALES BY CATEGORY CHART =================
    <?php
    $category_labels = [];
    $category_data = [];
    try {
        $select_cat = $pdo->prepare("
            SELECT p.category, COALESCE(SUM(id.qty * id.saleprice), 0) as total
            FROM tbl_category c
            LEFT JOIN tbl_product p ON c.category = p.category
            LEFT JOIN tbl_invoice_details id ON p.pid = id.product_id
            GROUP BY p.category
            ORDER BY total DESC
            LIMIT 5
        ");
        $select_cat->execute();
        while($row = $select_cat->fetch(PDO::FETCH_ASSOC)) {
            if($row['category']) {
                $category_labels[] = $row['category'];
                $category_data[] = $row['total'];
            }
        }
    } catch(Exception $e) {
        $category_labels = ['No Data'];
        $category_data = [0];
    }
    ?>
    
    var ctx2 = document.getElementById('categoryChart').getContext('2d');
    var categoryChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($category_data); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    
    // ================= MONTHLY SALES CHART (This Year) =================
    <?php
    $monthly_labels = [];
    $monthly_data = [];
    try {
        for($i = 1; $i <= 12; $i++) {
            $month = str_pad($i, 2, '0', STR_PAD_LEFT);
            $year = date('Y');
            $monthly_labels[] = date('M', mktime(0, 0, 0, $i, 1));
            
            $select_monthly = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total FROM tbl_invoice WHERE MONTH(order_date) = :month AND YEAR(order_date) = :year");
            $select_monthly->bindParam(':month', $month);
            $select_monthly->bindParam(':year', $year);
            $select_monthly->execute();
            $result = $select_monthly->fetch(PDO::FETCH_ASSOC);
            $monthly_data[] = $result['total'] ? $result['total'] : 0;
        }
    } catch(Exception $e) {
        for($i = 1; $i <= 12; $i++) {
            $monthly_labels[] = date('M', mktime(0, 0, 0, $i, 1));
            $monthly_data[] = 0;
        }
    }
    ?>
    
    var ctx3 = document.getElementById('monthlySalesChart').getContext('2d');
    var monthlySalesChart = new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets: [{
                label: 'Monthly Sales (₱)',
                data: <?php echo json_encode($monthly_data); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
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
});
</script>