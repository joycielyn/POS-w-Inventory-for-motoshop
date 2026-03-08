<?php
include_once 'connectdb.php';
session_start();

if($_SESSION['useremail'] == ""){
    header('location:../index.php');
    exit();
}

include_once "headeruser.php";
?>

<!-- Content Wrapper -->
<div class="content-wrapper" style="background:#f0f2f5;">

  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0">
            <i class="fas fa-history text-primary"></i>
            Transaction History
          </h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="userdashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Transaction History</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <!-- Summary Row -->
      <?php
        $today_total = 0; $today_count = 0;
        try {
          $q = $pdo->prepare("SELECT COALESCE(SUM(total),0) as t, COUNT(*) as c FROM tbl_invoice WHERE DATE(order_date)=CURDATE()");
          $q->execute();
          $r = $q->fetch(PDO::FETCH_ASSOC);
          $today_total = $r['t']; $today_count = $r['c'];
        } catch(Exception $e){}
      ?>
      <div class="row mb-3">
        <div class="col-md-4">
          <div class="info-box bg-primary shadow-sm" style="border-radius:10px;">
            <span class="info-box-icon"><i class="fas fa-receipt"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Today's Transactions</span>
              <span class="info-box-number"><?php echo number_format($today_count); ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="info-box bg-success shadow-sm" style="border-radius:10px;">
            <span class="info-box-icon"><i class="fas fa-peso-sign"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Today's Sales</span>
              <span class="info-box-number">₱<?php echo number_format($today_total, 2); ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="info-box bg-info shadow-sm" style="border-radius:10px;">
            <span class="info-box-icon"><i class="fas fa-user-tie"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Cashier</span>
              <span class="info-box-number" style="font-size:1.1rem;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card" style="border-radius:12px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <div class="card-header d-flex align-items-center" style="background:#fff; border-radius:12px 12px 0 0; border-bottom:1px solid #f0f0f0;">
          <h5 class="m-0 font-weight-bold"><i class="fas fa-list text-primary mr-2"></i>All Transactions</h5>
          <a href="userpos.php" class="btn btn-primary btn-sm ml-auto">
            <i class="fas fa-plus-circle mr-1"></i> New Sale
          </a>
        </div>
        <div class="card-body">
          <table class="table table-hover table-striped" id="transactionTable">
            <thead class="thead-dark">
              <tr>
                <th>#</th>
                <th>Invoice ID</th>
                <th>Cashier</th>
                <th>Date & Time</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Discount</th>
                <th class="text-right">VAT</th>
                <th class="text-right">Total</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Change</th>
                <th class="text-center">Type</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $sel = $pdo->prepare("SELECT * FROM tbl_invoice ORDER BY invoice_id DESC");
                $sel->execute();
                $i = 1;
                while($row = $sel->fetch(PDO::FETCH_OBJ)):
              ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td>
                  <span class="badge badge-secondary font-weight-bold">
                    #<?php echo str_pad($row->invoice_id, 4, '0', STR_PAD_LEFT); ?>
                  </span>
                </td>
                <td><small><?php echo !empty($row->cashier) ? htmlspecialchars($row->cashier) : '<span class="text-muted">—</span>'; ?></small></td>
                <td><small><?php echo date('M d, Y h:i A', strtotime($row->order_date)); ?></small></td>
                <td class="text-right">₱<?php echo number_format($row->subtotal, 2); ?></td>
                <td class="text-right text-danger">
                  <?php echo $row->discount > 0 ? '-₱'.number_format(($row->discount/100)*$row->subtotal, 2) : '<span class="text-muted">—</span>'; ?>
                </td>
                <td class="text-right text-secondary">₱<?php echo number_format(isset($row->vat) ? $row->vat : 0, 2); ?></td>
                <td class="text-right font-weight-bold text-primary">₱<?php echo number_format($row->total, 2); ?></td>
                <td class="text-right text-success">₱<?php echo number_format($row->paid, 2); ?></td>
                <td class="text-right text-info">₱<?php echo number_format($row->due, 2); ?></td>
                <td class="text-center">
                  <span class="badge badge-warning"><?php echo htmlspecialchars($row->payment_type); ?></span>
                </td>
                <td class="text-center">
                  <a href="printbill.php?id=<?php echo $row->invoice_id; ?>"
                     class="btn btn-sm btn-primary" target="_blank"
                     data-toggle="tooltip" title="Print Receipt">
                    <i class="fas fa-print"></i>
                  </a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</div>

<?php include_once "footer.php"; ?>

<style>
.content-wrapper { background: #f0f2f5; }
.card { border-radius: 12px !important; }
.info-box { margin-bottom: 0; }
.table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.4px; }
.table td { vertical-align: middle; font-size: 0.875rem; }
</style>

<script>
$(document).ready(function(){
  $('[data-toggle="tooltip"]').tooltip();
  $('#transactionTable').DataTable({
    order: [[0, 'desc']],
    pageLength: 15,
    responsive: true,
    language: {
      search: '<i class="fas fa-search"></i> Search:',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_ transactions',
      paginate: {
        previous: '<i class="fas fa-chevron-left"></i>',
        next: '<i class="fas fa-chevron-right"></i>'
      }
    }
  });
});
</script>
