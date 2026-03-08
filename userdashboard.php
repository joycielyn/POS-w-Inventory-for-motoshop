<?php
include_once 'connectdb.php';
session_start();

if($_SESSION['useremail'] == ""){
    header('location:../index.php');
}

include_once "headeruser.php";

// ================= TODAY'S SALES =================
$today_sales       = 0;
$today_transactions = 0;
try {
    $q = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as transactions FROM tbl_invoice WHERE DATE(order_date) = CURDATE()");
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);
    $today_sales        = $row['total'];
    $today_transactions = $row['transactions'];
} catch(Exception $e) {}

// ================= TOTAL ITEMS SOLD TODAY =================
$today_items = 0;
try {
    $q = $pdo->prepare("
        SELECT COALESCE(SUM(d.qty), 0) as items
        FROM tbl_invoice_details d
        INNER JOIN tbl_invoice i ON d.invoice_id = i.invoice_id
        WHERE DATE(i.order_date) = CURDATE()
    ");
    $q->execute();
    $today_items = $q->fetch(PDO::FETCH_ASSOC)['items'];
} catch(Exception $e) {}

// ================= TODAY'S SALES BREAKDOWN (for modal) =================
$today_breakdown = [];
try {
    $q = $pdo->prepare("
        SELECT p.product, SUM(d.qty) as qty, SUM(d.qty * d.saleprice) as subtotal
        FROM tbl_invoice_details d
        INNER JOIN tbl_invoice i ON d.invoice_id = i.invoice_id
        INNER JOIN tbl_product p ON d.product_id = p.pid
        WHERE DATE(i.order_date) = CURDATE()
        GROUP BY d.product_id
        ORDER BY subtotal DESC
    ");
    $q->execute();
    $today_breakdown = $q->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// ================= LOW STOCK ALERT (stock <= 10) =================
$low_stock = [];
try {
    $q = $pdo->prepare("SELECT product, stock, category FROM tbl_product WHERE stock <= 10 AND stock > 0 ORDER BY stock ASC LIMIT 8");
    $q->execute();
    $low_stock = $q->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

$low_stock_count = count($low_stock);

// ================= RECENT TRANSACTIONS (last 5) =================
$recent_transactions = [];
try {
    $q = $pdo->prepare("
        SELECT invoice_id, total, order_date, cashier
        FROM tbl_invoice
        ORDER BY order_date DESC
        LIMIT 5
    ");
    $q->execute();
    $recent_transactions = $q->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}
?>

<!-- Content Wrapper -->
<div class="content-wrapper">

    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-tachometer-alt text-primary"></i>
                        Dashboard
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="userdashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- ===== STAT CARDS ROW ===== -->
            <div class="row">

                <!-- Today's Sales Card (clickable → opens modal) -->
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="stat-card card-sales" data-toggle="modal" data-target="#todaysSalesModal" style="cursor:pointer;" title="Click to view breakdown">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <p class="stat-label">Today's Sales</p>
                            <h2 class="stat-value">₱<?php echo number_format($today_sales, 2); ?></h2>
                            <small class="stat-sub"><i class="fas fa-mouse-pointer"></i> Click to view breakdown</small>
                        </div>
                    </div>
                </div>

                <!-- Total Transactions Card -->
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="stat-card card-transactions">
                        <div class="stat-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="stat-info">
                            <p class="stat-label">Total Transactions</p>
                            <h2 class="stat-value"><?php echo number_format($today_transactions); ?></h2>
                            <small class="stat-sub">Transactions today</small>
                        </div>
                    </div>
                </div>

                <!-- Total Items Sold Card -->
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="stat-card card-items">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-info">
                            <p class="stat-label">Total Items Sold</p>
                            <h2 class="stat-value"><?php echo number_format($today_items); ?></h2>
                            <small class="stat-sub">Items sold today</small>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /.STAT CARDS ROW -->

            <!-- ===== LOW STOCK ALERT + RECENT TRANSACTIONS ===== -->
            <div class="row mt-2">

                <!-- Low Stock Alert -->
                <div class="col-lg-5 col-12">
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                Low Stock Alert
                                <?php if($low_stock_count > 0): ?>
                                    <span class="badge badge-warning ml-2"><?php echo $low_stock_count; ?></span>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($low_stock)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>
                                    All products are well-stocked!
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover m-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th class="text-center">Stock</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($low_stock as $item): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($item['product']); ?></strong></td>
                                                <td><small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small></td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo $item['stock'] <= 5 ? 'badge-danger' : 'badge-warning'; ?>">
                                                        <?php echo $item['stock']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if($item['stock'] <= 5): ?>
                                                        <span class="badge badge-danger"><i class="fas fa-fire"></i> Critical</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Low</span>
                                                    <?php endif; ?>
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

                <!-- Recent Transactions -->
                <div class="col-lg-7 col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history text-primary"></i>
                                Recent Transactions
                            </h3>
                            <div class="card-tools">
                                <a href="userorderlist.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list"></i> View All
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($recent_transactions)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No transactions yet today.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover m-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Invoice ID</th>
                                                <th>Cashier</th>
                                                <th>Date & Time</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($recent_transactions as $i => $txn): ?>
                                            <tr>
                                                <td><?php echo $i + 1; ?></td>
                                                <td>
                                                    <span class="badge badge-secondary">
                                                        #<?php echo str_pad($txn['invoice_id'], 4, '0', STR_PAD_LEFT); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($txn['cashier'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <small><?php echo date('M d, Y h:i A', strtotime($txn['order_date'])); ?></small>
                                                </td>
                                                <td class="text-right">
                                                    <strong class="text-success">₱<?php echo number_format($txn['total'], 2); ?></strong>
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

            </div>
            <!-- /.ROW -->

        </div>
    </section>
</div>
<!-- /.content-wrapper -->


<!-- ================= TODAY'S SALES MODAL ================= -->
<div class="modal fade" id="todaysSalesModal" tabindex="-1" role="dialog" aria-labelledby="todaysSalesModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header" style="background: linear-gradient(135deg, #1a73e8, #0d47a1);">
                <h5 class="modal-title text-white" id="todaysSalesModalLabel">
                    <i class="fas fa-chart-line"></i> &nbsp;Today's Sales Breakdown
                    <small class="d-block" style="font-size:0.75rem; font-weight:400; opacity:0.85;">
                        <?php echo date('l, F d, Y'); ?>
                    </small>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <!-- Summary row -->
                <div class="row mb-3">
                    <div class="col-md-4 col-6 mb-3">
                        <div class="modal-stat-box bg-primary">
                            <div class="msb-icon"><i class="fas fa-dollar-sign"></i></div>
                            <div class="msb-info">
                                <small>Total Sales</small>
                                <strong>₱<?php echo number_format($today_sales, 2); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6 mb-3">
                        <div class="modal-stat-box bg-success">
                            <div class="msb-icon"><i class="fas fa-receipt"></i></div>
                            <div class="msb-info">
                                <small>Transactions</small>
                                <strong><?php echo number_format($today_transactions); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6 mb-3">
                        <div class="modal-stat-box bg-info">
                            <div class="msb-icon"><i class="fas fa-boxes"></i></div>
                            <div class="msb-info">
                                <small>Items Sold</small>
                                <strong><?php echo number_format($today_items); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products breakdown table -->
                <div class="card card-primary card-outline mb-0">
                    <div class="card-header py-2">
                        <h6 class="card-title m-0">
                            <i class="fas fa-list-alt"></i> Items Sold Today
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($today_breakdown)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                No sales recorded today yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped m-0">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th class="text-center">Qty Sold</th>
                                            <th class="text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($today_breakdown as $i => $item): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><strong><?php echo htmlspecialchars($item['product']); ?></strong></td>
                                            <td class="text-center">
                                                <span class="badge badge-primary"><?php echo $item['qty']; ?></span>
                                            </td>
                                            <td class="text-right text-success font-weight-bold">
                                                ₱<?php echo number_format($item['subtotal'], 2); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="2" class="text-right font-weight-bold">TOTAL</td>
                                            <td class="text-center font-weight-bold"><?php echo number_format($today_items); ?></td>
                                            <td class="text-right font-weight-bold text-primary">₱<?php echo number_format($today_sales, 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <a href="userorderlist.php" class="btn btn-primary">
                    <i class="fas fa-history"></i> View All Transactions
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>

        </div>
    </div>
</div>
<!-- /.MODAL -->


<?php include_once "footer.php"; ?>

<style>
/* ===== STAT CARDS ===== */
.stat-card {
    display: flex;
    align-items: center;
    border-radius: 14px;
    padding: 22px 24px;
    margin-bottom: 20px;
    color: #fff;
    box-shadow: 0 4px 18px rgba(0,0,0,0.13);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 28px rgba(0,0,0,0.18);
}

.stat-card::after {
    content: '';
    position: absolute;
    right: -20px;
    top: -20px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
}

.card-sales        { background: linear-gradient(135deg, #1a73e8, #0d47a1); }
.card-transactions { background: linear-gradient(135deg, #28a745, #145a24); }
.card-items        { background: linear-gradient(135deg, #fd7e14, #b84a00); }

.stat-icon {
    font-size: 2.8rem;
    margin-right: 20px;
    opacity: 0.85;
    min-width: 55px;
    text-align: center;
}

.stat-info {
    flex: 1;
}

.stat-label {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

.stat-value {
    margin: 4px 0 2px;
    font-size: 1.85rem;
    font-weight: 800;
    line-height: 1.1;
}

.stat-sub {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* ===== MODAL STAT BOXES ===== */
.modal-stat-box {
    display: flex;
    align-items: center;
    border-radius: 10px;
    padding: 14px 16px;
    color: #fff;
    box-shadow: 0 3px 10px rgba(0,0,0,0.12);
}

.msb-icon {
    font-size: 1.8rem;
    margin-right: 14px;
    opacity: 0.85;
}

.msb-info {
    display: flex;
    flex-direction: column;
}

.msb-info small {
    font-size: 0.75rem;
    opacity: 0.85;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.msb-info strong {
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1.2;
}

/* ===== CARD STYLES ===== */
.card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    background: #fff;
    border-bottom: 1px solid #f0f0f0;
    padding: 14px 18px;
}

.card-title {
    font-weight: 600;
    font-size: 0.95rem;
}

/* ===== TABLE ===== */
.table th {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
    font-size: 0.88rem;
}

/* ===== MODAL ===== */
.modal-content {
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
    padding: 18px 24px;
}

.modal-dialog {
    animation: slideDown 0.28s ease;
}

@keyframes slideDown {
    from { transform: translateY(-30px); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
}

/* ===== CONTENT WRAPPER ===== */
.content-wrapper {
    background-color: #f4f6f9;
}
</style>
