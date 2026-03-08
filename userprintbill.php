<?php
include_once 'connectdb.php';
session_start();

if($_SESSION['useremail'] == ""){
    header('location:../index.php');
    exit();
}

include_once "headeruser.php";

// Get selected invoice details if an id is passed
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$invoice    = null;
$items      = [];

if($invoice_id > 0){
    try {
        $q = $pdo->prepare("SELECT * FROM tbl_invoice WHERE invoice_id = :id");
        $q->bindParam(':id', $invoice_id);
        $q->execute();
        $invoice = $q->fetch(PDO::FETCH_OBJ);

        $q2 = $pdo->prepare("SELECT * FROM tbl_invoice_details WHERE invoice_id = :id");
        $q2->bindParam(':id', $invoice_id);
        $q2->execute();
        $items = $q2->fetchAll(PDO::FETCH_OBJ);
    } catch(Exception $e){ $invoice = null; }
}
?>

<!-- Content Wrapper -->
<div class="content-wrapper" style="background:#f0f2f5;">

  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0">
            <i class="fas fa-file-invoice text-primary"></i>
            Sales Receipt
          </h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="userdashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="userorderlist.php">Transactions</a></li>
            <li class="breadcrumb-item active">Receipt</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content pb-4">
    <div class="container-fluid">
      <div class="row">

        <!-- LEFT: Search / Select Receipt -->
        <div class="col-lg-4 col-12 mb-3">
          <div class="card" style="border-radius:12px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <div class="card-header" style="background:#fff; border-radius:12px 12px 0 0; border-bottom:1px solid #f0f0f0;">
              <h6 class="m-0 font-weight-bold"><i class="fas fa-search text-primary mr-2"></i>Find Receipt</h6>
            </div>
            <div class="card-body">
              <form method="GET" action="userprintbill.php">
                <div class="input-group mb-3">
                  <select name="id" class="form-control" id="invoiceSelect">
                    <option value="">-- Select Invoice --</option>
                    <?php
                      $q = $pdo->prepare("SELECT invoice_id, order_date, total FROM tbl_invoice ORDER BY invoice_id DESC");
                      $q->execute();
                      while($r = $q->fetch(PDO::FETCH_OBJ)){
                        $sel = ($r->invoice_id == $invoice_id) ? 'selected' : '';
                        echo '<option value="'.$r->invoice_id.'" '.$sel.'>'
                             .'#'.str_pad($r->invoice_id, 4,'0',STR_PAD_LEFT)
                             .' — '.date('M d, Y', strtotime($r->order_date))
                             .' — ₱'.number_format($r->total,2)
                             .'</option>';
                      }
                    ?>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                  <i class="fas fa-eye mr-1"></i> View Receipt
                </button>
              </form>

              <hr>

              <!-- Recent receipts quick links -->
              <small class="text-muted font-weight-bold d-block mb-2">RECENT TRANSACTIONS</small>
              <?php
                $qr = $pdo->prepare("SELECT invoice_id, order_date, total FROM tbl_invoice ORDER BY invoice_id DESC LIMIT 8");
                $qr->execute();
                while($rr = $qr->fetch(PDO::FETCH_OBJ)):
                  $active = ($rr->invoice_id == $invoice_id) ? 'btn-primary' : 'btn-outline-secondary';
              ?>
              <a href="userprintbill.php?id=<?php echo $rr->invoice_id; ?>"
                 class="btn btn-sm <?php echo $active; ?> btn-block text-left mb-1">
                <i class="fas fa-receipt mr-1"></i>
                #<?php echo str_pad($rr->invoice_id, 4,'0',STR_PAD_LEFT); ?>
                &nbsp;·&nbsp;
                <?php echo date('M d, Y', strtotime($rr->order_date)); ?>
                &nbsp;·&nbsp;
                <strong>₱<?php echo number_format($rr->total,2); ?></strong>
              </a>
              <?php endwhile; ?>
            </div>
          </div>
        </div>

        <!-- RIGHT: Receipt Preview -->
        <div class="col-lg-8 col-12">
          <?php if($invoice): ?>
          <div class="card" style="border-radius:12px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <div class="card-header d-flex align-items-center" style="background:#fff; border-radius:12px 12px 0 0; border-bottom:1px solid #f0f0f0;">
              <h6 class="m-0 font-weight-bold">
                <i class="fas fa-file-alt text-primary mr-2"></i>
                Invoice #<?php echo str_pad($invoice->invoice_id,4,'0',STR_PAD_LEFT); ?>
              </h6>
              <div class="ml-auto">
                <a href="printbill.php?id=<?php echo $invoice->invoice_id; ?>"
                   target="_blank" class="btn btn-primary btn-sm">
                  <i class="fas fa-print mr-1"></i> Print PDF
                </a>
                <a href="userorderlist.php" class="btn btn-secondary btn-sm ml-1">
                  <i class="fas fa-list mr-1"></i> All Transactions
                </a>
              </div>
            </div>

            <!-- Receipt Preview -->
            <div class="card-body d-flex justify-content-center" style="background:#f8f9fa;">
              <div id="receiptPreview" style="width:320px; background:#fff; border:1px solid #ddd; border-radius:8px; padding:24px; font-family:'Courier New', monospace; font-size:13px; box-shadow:0 4px 20px rgba(0,0,0,0.1);">

                <!-- Store Header -->
                <div style="text-align:center; border-bottom:2px dashed #333; padding-bottom:12px; margin-bottom:12px;">
                  <div style="font-size:16px; font-weight:900; letter-spacing:1px;">Concepcion Motorcycle Shop</div>
                  <div style="font-size:11px; color:#666;">PHONE: 09620433464</div>
                  <div style="font-size:11px; color:#666;">concepcionmotorshop@gmail.com</div>
                </div>

                <!-- Invoice Info -->
                <div style="margin-bottom:10px; font-size:12px;">
                  <div style="display:flex; justify-content:space-between;">
                    <span>Invoice #:</span>
                    <strong><?php echo str_pad($invoice->invoice_id,4,'0',STR_PAD_LEFT); ?></strong>
                  </div>
                  <div style="display:flex; justify-content:space-between;">
                    <span>Date:</span>
                    <strong><?php echo date('M d, Y h:i A', strtotime($invoice->order_date)); ?></strong>
                  </div>
                  <div style="display:flex; justify-content:space-between;">
                    <span>Cashier:</span>
                    <strong><?php echo !empty($invoice->cashier) ? htmlspecialchars($invoice->cashier) : '—'; ?></strong>
                  </div>
                </div>

                <!-- Items -->
                <div style="border-top:1px dashed #333; border-bottom:1px dashed #333; padding:8px 0; margin-bottom:10px;">
                  <div style="display:flex; font-weight:900; font-size:11px; margin-bottom:6px; color:#333;">
                    <span style="flex:2;">PRODUCT</span>
                    <span style="flex:1; text-align:center;">QTY</span>
                    <span style="flex:1; text-align:right;">PRICE</span>
                    <span style="flex:1; text-align:right;">TOTAL</span>
                  </div>
                  <?php foreach($items as $item): ?>
                  <div style="display:flex; font-size:12px; margin-bottom:4px;">
                    <span style="flex:2; word-break:break-word;"><?php echo htmlspecialchars($item->product_name); ?></span>
                    <span style="flex:1; text-align:center;"><?php echo $item->qty; ?></span>
                    <span style="flex:1; text-align:right;">₱<?php echo number_format($item->rate, 2); ?></span>
                    <span style="flex:1; text-align:right;">₱<?php echo number_format($item->rate * $item->qty, 2); ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>

                <!-- Totals -->
                <div style="font-size:12px; margin-bottom:10px;">
                  <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                    <span>Subtotal</span>
                    <span>₱<?php echo number_format($invoice->subtotal, 2); ?></span>
                  </div>
                  <?php if($invoice->discount > 0): ?>
                  <div style="display:flex; justify-content:space-between; margin-bottom:3px; color:#c0392b;">
                    <span>Discount (<?php echo $invoice->discount; ?>%)</span>
                    <span>- ₱<?php echo number_format(($invoice->discount/100)*$invoice->subtotal, 2); ?></span>
                  </div>
                  <?php endif; ?>
                  <div style="display:flex; justify-content:space-between; margin-bottom:3px; color:#555;">
                    <span>VAT (12%)</span>
                    <span>₱<?php echo number_format(isset($invoice->vat) ? $invoice->vat : 0, 2); ?></span>
                  </div>
                  <div style="display:flex; justify-content:space-between; font-size:15px; font-weight:900; border-top:1px solid #333; padding-top:6px; margin-top:4px;">
                    <span>TOTAL</span>
                    <span>₱<?php echo number_format($invoice->total, 2); ?></span>
                  </div>
                  <div style="display:flex; justify-content:space-between; margin-top:4px; color:#27ae60;">
                    <span>PAID (<?php echo htmlspecialchars($invoice->payment_type); ?>)</span>
                    <span>₱<?php echo number_format($invoice->paid, 2); ?></span>
                  </div>
                  <div style="display:flex; justify-content:space-between; color:#2980b9; font-weight:700;">
                    <span>CHANGE</span>
                    <span>₱<?php echo number_format($invoice->due, 2); ?></span>
                  </div>
                </div>

                <!-- Footer -->
                <div style="text-align:center; border-top:2px dashed #333; padding-top:10px; font-size:11px; color:#777;">
                  Thank you and please come again!<br>
                  
                </div>

              </div>
            </div>

          </div>
          <?php else: ?>
          <!-- No invoice selected -->
          <div class="card text-center py-5" style="border-radius:12px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <div class="card-body">
              <i class="fas fa-file-invoice fa-5x text-muted mb-4" style="opacity:0.3;"></i>
              <h4 class="text-muted font-weight-bold">No Receipt Selected</h4>
              <p class="text-muted">Select an invoice from the left panel to preview the receipt.</p>
              <a href="userpos.php" class="btn btn-primary mt-2">
                <i class="fas fa-cash-register mr-1"></i> Go to POS
              </a>
            </div>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>
</div>

<?php include_once "footer.php"; ?>

<style>
.content-wrapper { background: #f0f2f5; }
.card { border-radius: 12px !important; }
</style>
