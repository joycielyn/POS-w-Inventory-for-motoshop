<?php
ob_start();
include_once 'connectdb.php';
session_start();

if($_SESSION['useremail'] == ""){
    header('location:../index.php');
    exit();
}

// ===== SAVE ORDER =====
if(isset($_POST['btnsaveorder'])){
    $orderdate    = date('Y-m-d H:i:s');
    $subtotal     = $_POST['txtsubtotal'];
    $discount     = $_POST['txtdiscount'];
    $vat = isset($_POST['txtvat']) ? $_POST['txtvat'] : 0;
    $total        = $_POST['txttotal'];
    $due          = $_POST['txtdue'];
    $paid         = $_POST['txtpaid'];
    $cashier      = $_SESSION['username'];

    if(floatval($paid) < floatval($total)){
        $_SESSION['pos_status']      = "Insufficient payment! Amount paid is less than total.";
        $_SESSION['pos_status_code'] = "error";
        header('location:userpos.php');
        exit();
    }
    if(!isset($_POST['pid_arr'])){
    $_SESSION['pos_status'] = "No products in cart!";
    $_SESSION['pos_status_code'] = "error";
    header('location:userpos.php');
    exit();
}

        $arr_pid     = $_POST['pid_arr'];
        $arr_barcode = $_POST['barcode_arr'];
        $arr_name    = $_POST['product_arr'];
        $arr_stock   = $_POST['stock_c_arr'];
        $arr_qty     = $_POST['quantity_arr'];
        $arr_price   = $_POST['price_c_arr'];
        $arr_total   = $_POST['saleprice_arr'];

    // Check which optional columns exist in tbl_invoice
    $hasVat     = false;
    $hasCashier = false;
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM tbl_invoice");
        foreach($chk->fetchAll(PDO::FETCH_ASSOC) as $col){
            if($col['Field'] === 'vat')     $hasVat     = true;
            if($col['Field'] === 'cashier') $hasCashier = true;
        }
    } catch(Exception $e){}

    // Build INSERT dynamically based on existing columns
    $cols   = "order_date, subtotal, discount, total, payment_type, due, paid";
    $params = ":order_date, :subtotal, :discount, :total, 'Cash', :due, :paid";
    if($hasVat)     { $cols .= ", vat";     $params .= ", :vat"; }
    if($hasCashier) { $cols .= ", cashier"; $params .= ", :cashier"; }

    try {
        $insert = $pdo->prepare("INSERT INTO tbl_invoice ($cols) VALUES ($params)");
        $insert->bindParam(':order_date', $orderdate);
        $insert->bindParam(':subtotal',   $subtotal);
        $insert->bindParam(':discount',   $discount);
        $insert->bindParam(':total',      $total);
        $insert->bindParam(':due',        $due);
        $insert->bindParam(':paid',       $paid);
        if($hasVat)     $insert->bindParam(':vat',     $vat);
        if($hasCashier) $insert->bindParam(':cashier', $cashier);
        $insert->execute();
    } catch(Exception $e){
        $_SESSION['pos_status']      = "DB Error (invoice): " . $e->getMessage();
        $_SESSION['pos_status_code'] = "error";
        header('location:userpos.php');
        exit();
    }

    $invoice_id = (int)$pdo->lastInsertId();

    if($invoice_id > 0){
        for($i = 0; $i < count($arr_pid); $i++){
            $rem_qty = intval($arr_stock[$i]) - intval($arr_qty[$i]);
            if($rem_qty >= 0){
                $upd = $pdo->prepare("UPDATE tbl_product SET stock = :s WHERE pid = :p");
                $upd->bindParam(':s', $rem_qty);
                $upd->bindParam(':p', $arr_pid[$i]);
                $upd->execute();
            }
            $ins = $pdo->prepare("
               INSERT INTO tbl_invoice_details
                (invoice_id, barcode, product_id, product_name, qty, rate, saleprice)
                VALUES (:inv, :bc, :pid, :name, :qty, :rate, :sp)
            ");
            $ins->bindParam(':inv',  $invoice_id);
            $ins->bindParam(':bc',   $arr_barcode[$i]);
            $ins->bindParam(':pid',  $arr_pid[$i]);
            $ins->bindParam(':name', $arr_name[$i]);
            $ins->bindParam(':qty',  $arr_qty[$i]);
            $ins->bindParam(':rate', $arr_price[$i]);
            $ins->bindParam(':sp',   $arr_total[$i]);
            $ins->bindParam(':od',   $orderdate);
            $ins->execute();
        }
        // Store invoice_id in session for receipt redirect
        $_SESSION['last_invoice_id'] = $invoice_id;
        header('location:userpos.php?success=1&inv=' . $invoice_id);
        exit();
    }
}

// Fetch tax/discount defaults
$taxrow = null;
try {
    $q = $pdo->prepare("SELECT * FROM tbl_taxdis LIMIT 1");
    $q->execute();
    $taxrow = $q->fetch(PDO::FETCH_OBJ);
} catch(Exception $e){}

$default_discount = isset($taxrow->discount) ? (float)$taxrow->discount : 0;
$default_vat      = isset($taxrow->tax)      ? (float)$taxrow->tax      : 0;

ob_end_flush();

include_once "headeruser.php";
?>

<!-- Content Wrapper -->
<div class="content-wrapper" style="background:#f0f2f5;">

  <!-- Header -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0">
            <i class="fas fa-cash-register text-primary"></i>
            Point of Sale
          </h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="userdashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">POS</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <section class="content pb-3">
    <div class="container-fluid">
      <form action="userpos.php" method="POST" id="posForm">

        <div class="row">

          <!-- ============ LEFT: PRODUCT SEARCH + CART ============ -->
          <div class="col-lg-8 col-12">

            <!-- Search bar -->
            <div class="card mb-3" style="border-radius:12px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
              <div class="card-body py-3">
                <div class="row align-items-center">
                  <div class="col-md-6 mb-2 mb-md-0">
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-primary text-white border-0">
                          <i class="fas fa-search"></i>
                        </span>
                      </div>
                      <select class="form-control select2-product" id="productSelect" style="width:100%;">
                        <option value="">-- Search product by name --</option>
                        <?php
                          $sel = $pdo->prepare("SELECT * FROM tbl_product WHERE stock > 0 ORDER BY product ASC");
                          $sel->execute();
                          while($p = $sel->fetch(PDO::FETCH_OBJ)){
                            echo '<option value="'.$p->pid.'">'
                                 .htmlspecialchars($p->product)
                                 .' &nbsp;[Stock: '.$p->stock.'] &nbsp;₱'.number_format($p->saleprice,2)
                                 .'</option>';
                          }
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-dark text-white border-0">
                          <i class="fas fa-barcode"></i>
                        </span>
                      </div>
                      <input type="text" class="form-control" id="txtbarcode_id"
                             placeholder="Scan barcode or type barcode #"
                             autocomplete="off">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cart Table -->
            <div class="card" style="border-radius:12px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
              <div class="card-header d-flex align-items-center" style="border-radius:12px 12px 0 0; background:#fff; border-bottom:1px solid #f0f0f0;">
                <h5 class="m-0 font-weight-bold"><i class="fas fa-shopping-cart text-primary mr-2"></i>Cart</h5>
                <button type="button" class="btn btn-sm btn-outline-danger ml-auto" id="clearCartBtn">
                  <i class="fas fa-trash-alt"></i> Clear All
                </button>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive" style="max-height:420px; overflow-y:auto;">
                  <table class="table table-hover m-0" id="producttable">
                    <thead style="position:sticky; top:0; z-index:2;">
                      <tr class="thead-dark">
                        <th style="width:28%;">Product</th>
                        <th class="text-center" style="width:9%;">Unit</th>
                        <th class="text-center" style="width:9%;">Stock</th>
                        <th class="text-right" style="width:12%;">Price</th>
                        <th class="text-center" style="width:14%;">Qty</th>
                        <th class="text-right" style="width:13%;">Total</th>
                        <th class="text-center" style="width:7%;">Del</th>
                      </tr>
                    </thead>
                    <tbody id="itemtable" class="details">
                      <tr id="emptyrow">
                        <td colspan="7" class="text-center text-muted py-5">
                          <i class="fas fa-cart-plus fa-3x mb-3 d-block" style="opacity:0.3;"></i>
                          <span style="font-size:0.95rem;">No items added yet.<br>Search or scan a product to begin.</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- Cart footer: item count -->
              <div class="card-footer py-2 d-flex align-items-center" style="background:#f8f9fa; border-radius:0 0 12px 12px;">
                <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>
                  <span id="itemCountLabel">0 item(s) in cart</span>
                </small>
              </div>
            </div>

          </div>
          <!-- /.LEFT -->

          <!-- ============ RIGHT: TOTALS + PAYMENT ============ -->
          <div class="col-lg-4 col-12 mt-3 mt-lg-0">
            <div class="card" style="border-radius:12px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
              <div class="card-header" style="background:linear-gradient(135deg,#1a73e8,#0d47a1); border-radius:12px 12px 0 0;">
                <h5 class="m-0 text-white"><i class="fas fa-calculator mr-2"></i>Order Summary</h5>
              </div>
              <div class="card-body">

                <!-- Subtotal -->
                <div class="summary-row">
                  <span class="summary-label">Subtotal</span>
                  <div class="input-group input-group-sm summary-input">
                    <input type="text" class="form-control text-right font-weight-bold"
                           name="txtsubtotal" id="txtsubtotal_id" readonly value="0.00">
                    <div class="input-group-append"><span class="input-group-text">₱</span></div>
                  </div>
                </div>

                <!-- Discount % -->
                <div class="summary-row">
                  <span class="summary-label">Discount <small class="text-muted">(%)</small></span>
                  <div class="input-group input-group-sm summary-input">
                    <input type="text" class="form-control text-right"
                           name="txtdiscount" id="txtdiscount_p"
                           value="<?php echo $default_discount; ?>">
                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                  </div>
                </div>

                <!-- Discount Amount -->
                <div class="summary-row">
                  <span class="summary-label text-danger">Discount <small class="text-muted">(₱)</small></span>
                  <div class="input-group input-group-sm summary-input">
                    <input type="text" class="form-control text-right text-danger"
                           id="txtdiscount_n" readonly value="0.00">
                    <div class="input-group-append"><span class="input-group-text">₱</span></div>
                  </div>
                </div>

                <!-- VAT % -->
                <div class="summary-row">
                  <span class="summary-label">VAT <small class="text-muted">(%)</small></span>
                  <div class="input-group input-group-sm summary-input">
                    <?php
                      $presetVats = [0, 5, 10, 12, 20];
                    ?>
                    <select class="form-control" id="txtvat_p" name="txtvat_p">
                      <?php if(!in_array($default_vat, $presetVats)): ?>
                        <option value="<?php echo $default_vat; ?>" selected><?php echo $default_vat; ?>%</option>
                      <?php endif; ?>
                      <?php foreach($presetVats as $v): ?>
                        <option value="<?php echo $v; ?>" <?php echo ($v == $default_vat) ? 'selected' : ''; ?>>
                          <?php echo $v; ?>%
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                  </div>
                </div>

                <!-- VAT Amount -->
                <div class="summary-row">
                  <span class="summary-label">VAT <small class="text-muted">(₱)</small></span>
                  <div class="input-group input-group-sm summary-input">
                    <input type="text" class="form-control text-right"
                           name="txtvat" id="txtvat_n" readonly value="0.00">
                    <div class="input-group-append"><span class="input-group-text">₱</span></div>
                  </div>
                </div>

                <hr class="my-2">

                <!-- TOTAL -->
                <div class="total-display">
                  <span>TOTAL AMOUNT</span>
                  <span id="totalDisplay">₱0.00</span>
                  <input type="hidden" name="txttotal" id="txttotal" value="0.00">
                </div>

                <hr class="my-2">

                <!-- Payment Type -->
                <div class="d-flex align-items-center mb-2">
                  <i class="fas fa-money-bill-wave text-success mr-2"></i>
                  <span class="font-weight-bold text-success">CASH PAYMENT</span>
                </div>

                <!-- Amount Paid -->
                <div class="summary-row">
                  <span class="summary-label font-weight-bold">Amount Paid</span>
                  <div class="input-group input-group-sm summary-input">
                    <div class="input-group-prepend"><span class="input-group-text bg-success text-white">₱</span></div>
                    <input type="number" class="form-control text-right font-weight-bold"
                           name="txtpaid" id="txtpaid"
                           placeholder="0.00" min="0" step="0.01">
                  </div>
                </div>

                <!-- Change -->
                <div class="summary-row">
                  <span class="summary-label text-info font-weight-bold">Change</span>
                  <div class="input-group input-group-sm summary-input">
                    <div class="input-group-prepend"><span class="input-group-text bg-info text-white">₱</span></div>
                    <input type="text" class="form-control text-right font-weight-bold text-info"
                           name="txtdue" id="txtdue" readonly value="0.00">
                  </div>
                </div>

                <!-- Quick Cash Buttons -->
                <div class="mt-2 mb-3">
                  <small class="text-muted d-block mb-1"><i class="fas fa-bolt mr-1"></i>Quick Amount</small>
                  <div class="d-flex flex-wrap" id="quickCash">
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-btn mr-1 mb-1" data-val="20">₱20</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-btn mr-1 mb-1" data-val="50">₱50</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-btn mr-1 mb-1" data-val="100">₱100</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-btn mr-1 mb-1" data-val="200">₱200</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-btn mr-1 mb-1" data-val="500">₱500</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-btn mr-1 mb-1" data-val="1000">₱1K</button>
                    <button type="button" class="btn btn-sm btn-success quick-btn mr-1 mb-1" id="exactBtn">Exact</button>
                  </div>
                </div>

                <!-- Save Order Button -->
                <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold"
                        name="btnsaveorder" id="btnsaveorder"
                        style="border-radius:10px; letter-spacing:0.5px;">
                  <i class="fas fa-check-circle mr-2"></i> PROCESS SALE
                </button>

              </div>
            </div>
          </div>
          <!-- /.RIGHT -->

        </div>
      </form>
    </div>
  </section>
</div>
<!-- /.content-wrapper -->

<?php include_once "footer.php"; ?>

<!-- ========== SUCCESS / RECEIPT MODAL ========== -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content" style="border-radius:12px; overflow:hidden;">
      <div class="modal-header" style="background:linear-gradient(135deg,#28a745,#145a24);">
        <h5 class="modal-title text-white"><i class="fas fa-check-circle mr-2"></i>Sale Successful!</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="fas fa-receipt fa-4x text-success mb-3"></i>
        <h4 class="font-weight-bold">Transaction Complete</h4>
        <p class="text-muted mb-4">Sale has been recorded and inventory updated.</p>
        <div id="receiptSummaryBox" class="text-left bg-light rounded p-3 mb-3" style="font-size:0.9rem;"></div>
      </div>
      <div class="modal-footer justify-content-center">
        <a href="#" id="printReceiptBtn" class="btn btn-primary btn-lg" target="_blank">
          <i class="fas fa-print mr-2"></i> Print Receipt
        </a>
        <a href="userpos.php" class="btn btn-success btn-lg">
          <i class="fas fa-plus-circle mr-2"></i> New Sale
        </a>
      </div>
    </div>
  </div>
</div>

<style>
/* ===== LAYOUT ===== */
.content-wrapper { background: #f0f2f5; }

/* ===== SUMMARY ROWS ===== */
.summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.summary-label {
  font-size: 0.82rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #555;
  min-width: 100px;
}
.summary-input {
  max-width: 160px;
}

/* ===== TOTAL DISPLAY ===== */
.total-display {
  background: linear-gradient(135deg,#1a73e8,#0d47a1);
  color: #fff;
  border-radius: 10px;
  padding: 14px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 800;
  font-size: 1rem;
  margin-bottom: 8px;
}
.total-display span:last-child {
  font-size: 1.5rem;
}

/* ===== CART TABLE ===== */
#producttable thead th {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  padding: 10px 10px;
  white-space: nowrap;
}
#producttable tbody td {
  vertical-align: middle;
  font-size: 0.88rem;
  padding: 8px 10px;
}
.qty-input {
  width: 65px !important;
  text-align: center;
  font-weight: 700;
  border-radius: 6px;
}

/* ===== CARD ===== */
.card { border-radius: 12px !important; }
.card-header { padding: 14px 18px; }

/* ===== QUICK BUTTONS ===== */
.quick-btn { font-size: 0.78rem; padding: 3px 8px; border-radius: 6px; }

/* ===== SELECT2 override ===== */
.select2-container .select2-selection--single {
  height: 38px !important;
  border-radius: 0 6px 6px 0 !important;
}
.select2-container .select2-selection--single .select2-selection__rendered {
  line-height: 38px !important;
}
.select2-container .select2-selection--single .select2-selection__arrow {
  height: 36px !important;
}

/* ===== BADGE STYLES ===== */
.badge-stock { font-size: 0.78rem; }
</style>

<script>
$(document).ready(function(){

  // ===== SELECT2 INIT =====
  $('.select2-product').select2({
    placeholder: '-- Search product by name --',
    allowClear: true,
    width: '100%'
  });

  var productArr = [];

  // ===== ADD ROW TO CART =====
  function addRow(pid, product, saleprice, stock, barcode, unit){
    unit = unit ? unit : 'pcs';

    // If already in cart, increment qty
    if($.inArray(String(pid), productArr) !== -1){
      var curQty  = parseInt($('#qty_id' + pid).val()) + 1;
      var curStock = parseInt($('#stock_idd' + pid).val());
      if(curQty > curStock){
        Swal.fire({icon:'warning', title:'Stock Limit!',
          text: 'Only ' + curStock + ' unit(s) available for this product.',
          confirmButtonColor:'#f39c12'});
        return;
      }
      $('#qty_id' + pid).val(curQty);
      var newTotal = curQty * parseFloat($('#price_idd' + pid).val());
      $('#saleprice_id'  + pid).text(newTotal.toFixed(2));
      $('#saleprice_idd' + pid).val(newTotal.toFixed(2));
      calculate();
      return;
    }

    // New row
    productArr.push(String(pid));
    $('#emptyrow').hide();

    var stockBadge = stock <= 5
      ? '<span class="badge badge-danger badge-stock">' + stock + '</span>'
      : (stock <= 10
          ? '<span class="badge badge-warning badge-stock">' + stock + '</span>'
          : '<span class="badge badge-success badge-stock">' + stock + '</span>');

    var tr = '<tr id="row_' + pid + '">'
      + '<input type="hidden" name="barcode_arr[]"  id="barcode_id'  + pid + '" value="' + barcode   + '">'
      + '<input type="hidden" name="pid_arr[]"      class="pid"      value="' + pid       + '">'
      + '<input type="hidden" name="product_arr[]"  class="product"  value="' + product   + '">'
      + '<input type="hidden" name="stock_c_arr[]"  class="stock_C"  id="stock_idd'  + pid + '" value="' + stock + '">'
      + '<input type="hidden" name="price_c_arr[]"  class="price_C"  id="price_idd'  + pid + '" value="' + saleprice + '">'
      + '<input type="hidden" name="saleprice_arr[]" class="saleprice" id="saleprice_idd' + pid + '" value="' + saleprice + '">'

      + '<td>'
      +   '<span class="font-weight-bold">' + product + '</span>'
      + '</td>'

      + '<td class="text-center">'
      +   '<span class="badge badge-secondary">' + unit + '</span>'
      + '</td>'

      + '<td class="text-center" id="stock_cell_' + pid + '">' + stockBadge + '</td>'

      + '<td class="text-right">'
      +   '<span class="text-warning font-weight-bold">₱' + parseFloat(saleprice).toFixed(2) + '</span>'
      + '</td>'

      + '<td class="text-center">'
      +   '<div class="input-group input-group-sm justify-content-center">'
      +     '<div class="input-group-prepend">'
      +       '<button type="button" class="btn btn-sm btn-outline-secondary qty-minus" data-pid="' + pid + '">-</button>'
      +     '</div>'
      +     '<input type="number" class="form-control qty-input qty" name="quantity_arr[]"'
      +       ' id="qty_id' + pid + '" value="1" min="1" max="' + stock + '">'
      +     '<div class="input-group-append">'
      +       '<button type="button" class="btn btn-sm btn-outline-secondary qty-plus" data-pid="' + pid + '">+</button>'
      +     '</div>'
      +   '</div>'
      + '</td>'

      + '<td class="text-right">'
      +   '<span class="text-success font-weight-bold" id="saleprice_id' + pid + '">' + parseFloat(saleprice).toFixed(2) + '</span>'
      + '</td>'

      + '<td class="text-center">'
      +   '<button type="button" class="btn btn-danger btn-sm btnremove" data-id="' + pid + '">'
      +     '<i class="fas fa-trash"></i>'
      +   '</button>'
      + '</td>'

      + '</tr>';

    $('#itemtable').append(tr);
    calculate();
    updateItemCount();
  }

  // ===== PRODUCT SELECT =====
  $('#productSelect').on('change', function(){
    var pid = $(this).val();
    if(!pid) return;
    $.getJSON('getproduct.php', {id: pid}, function(data){
      if(data && data.pid){
        addRow(data.pid, data.product, data.saleprice, data.stock, data.barcode, data.product_unit);
      }
    });
    $(this).val('').trigger('change');
  });

  // ===== BARCODE SCAN =====
  $('#txtbarcode_id').on('change', function(){
    var code = $(this).val().trim();
    if(!code) return;
    $.getJSON('getproduct.php', {id: code}, function(data){
      if(data && data.pid){
        addRow(data.pid, data.product, data.saleprice, data.stock, data.barcode, data.product_unit);
      } else {
        Swal.fire({icon:'error', title:'Not Found!',
          text:'No product found for barcode: ' + code,
          confirmButtonColor:'#d33'});
      }
    });
    $(this).val('');
  });

  // ===== QTY MINUS =====
  $(document).on('click', '.qty-minus', function(){
    var pid = $(this).data('pid');
    var input = $('#qty_id' + pid);
    var cur = parseInt(input.val());
    if(cur > 1){
      input.val(cur - 1).trigger('change');
    }
  });

  // ===== QTY PLUS =====
  $(document).on('click', '.qty-plus', function(){
    var pid = $(this).data('pid');
    var input  = $('#qty_id' + pid);
    var stock  = parseInt($('#stock_idd' + pid).val());
    var cur    = parseInt(input.val());
    if(cur < stock){
      input.val(cur + 1).trigger('change');
    } else {
      Swal.fire({icon:'warning', title:'Stock Limit!',
        text:'Only ' + stock + ' unit(s) available.',
        confirmButtonColor:'#f39c12'});
    }
  });

  // ===== QTY MANUAL INPUT =====
  $(document).on('change keyup', '.qty', function(){
    var tr    = $(this).closest('tr');
    var qty   = parseInt($(this).val()) || 1;
    var pid   = tr.find('.pid').val();
    var stock = parseInt($('#stock_idd' + pid).val());
    var price = parseFloat($('#price_idd' + pid).val());

    if(qty < 1)  qty = 1;
    if(qty > stock){
      Swal.fire({icon:'warning', title:'Exceeds Stock!',
        text:'Only ' + stock + ' unit(s) available.',
        confirmButtonColor:'#f39c12'});
      qty = stock;
    }
    $(this).val(qty);

    var lineTotal = qty * price;
    $('#saleprice_id'  + pid).text(lineTotal.toFixed(2));
    $('#saleprice_idd' + pid).val(lineTotal.toFixed(2));
    calculate();
  });

  // ===== REMOVE ROW =====
  $(document).on('click', '.btnremove', function(){
    var pid = $(this).data('id');
    productArr = $.grep(productArr, function(v){ return v != String(pid); });
    $('#row_' + pid).remove();
    if(productArr.length === 0) $('#emptyrow').show();
    calculate();
    updateItemCount();
  });

  // ===== CLEAR ALL =====
  $('#clearCartBtn').on('click', function(){
    if(productArr.length === 0) return;
    Swal.fire({
      title: 'Clear Cart?',
      text: 'Remove all items from cart?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, clear it!'
    }).then(function(result){
      if(result.isConfirmed){
        productArr = [];
        $('#itemtable tr:not(#emptyrow)').remove();
        $('#emptyrow').show();
        calculate();
        updateItemCount();
      }
    });
  });

  // ===== DISCOUNT =====
  $('#txtdiscount_p').on('keyup change', function(){ calculate(); });

  // ===== VAT =====
  $('#txtvat_p').on('change', function(){ calculate(); });

  // ===== PAID =====
  $('#txtpaid').on('keyup change', function(){ calculate(); });

  // ===== QUICK CASH BUTTONS =====
  $(document).on('click', '.quick-btn', function(){
    var val = $(this).data('val');
    $('#txtpaid').val(val).trigger('change');
  });

  $('#exactBtn').on('click', function(){
    var total = parseFloat($('#txttotal').val()) || 0;
    $('#txtpaid').val(total.toFixed(2)).trigger('change');
  });

  // ===== CALCULATE =====
  function calculate(){
    var subtotal = 0;
    $('.saleprice').each(function(){
      subtotal += parseFloat($(this).val()) || 0;
    });

    var discPct = parseFloat($('#txtdiscount_p').val()) || 0;
    var discAmt = (discPct / 100) * subtotal;
    $('#txtdiscount_n').val(discAmt.toFixed(2));

    var afterDisc = subtotal - discAmt;

    var vatPct  = parseFloat($('#txtvat_p').val()) || 0;
    var vatRate = vatPct / 100;
    var vatAmt  = vatRate > 0 ? (afterDisc - afterDisc / (1 + vatRate)) : 0;
    $('#txtvat_n').val(vatAmt.toFixed(2));

    var total = afterDisc;
    var paid  = parseFloat($('#txtpaid').val()) || 0;
    var change = paid - total;

    $('#txtsubtotal_id').val(subtotal.toFixed(2));
    $('#txttotal').val(total.toFixed(2));
    $('#totalDisplay').text('₱' + total.toFixed(2));
    $('#txtdue').val(change >= 0 ? change.toFixed(2) : '0.00');

    // Highlight change
    if(change >= 0 && paid > 0){
      $('#txtdue').removeClass('text-danger').addClass('text-info');
    } else if(paid > 0 && change < 0){
      $('#txtdue').removeClass('text-info').addClass('text-danger');
    }
  }

  // ===== UPDATE ITEM COUNT =====
  function updateItemCount(){
    var count = productArr.length;
    $('#itemCountLabel').text(count + ' item(s) in cart');
  }

  // ===== FORM SUBMIT VALIDATION =====
  $('#posForm').on('submit', function(e){
    var total = parseFloat($('#txttotal').val()) || 0;
    var paid  = parseFloat($('#txtpaid').val())  || 0;

    if(productArr.length === 0){
      e.preventDefault();
      Swal.fire({icon:'warning', title:'Empty Cart!',
        text:'Please add at least one product to the cart.',
        confirmButtonColor:'#f39c12'});
      return false;
    }

    if(total <= 0){
      e.preventDefault();
      Swal.fire({icon:'warning', title:'No Amount!',
        text:'Total amount must be greater than zero.',
        confirmButtonColor:'#f39c12'});
      return false;
    }

    if(paid < total){
      e.preventDefault();
      Swal.fire({icon:'error', title:'Insufficient Payment!',
        text: 'Amount paid (₱' + paid.toFixed(2) + ') is less than total (₱' + total.toFixed(2) + ').',
        confirmButtonColor:'#d33'});
      return false;
    }

    // Show loading on button
    $('#btnsaveorder').prop('disabled', true)
      .html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
  });

  // ===== SUCCESS MODAL (after redirect) =====
  <?php if(isset($_GET['success']) && isset($_GET['inv'])): ?>
    var invId = <?php echo (int)$_GET['inv']; ?>;
    $('#printReceiptBtn').attr('href', 'printbill.php?id=' + invId);
    $('#receiptSummaryBox').html(
      '<strong>Invoice #:</strong> ' + String(invId).padStart(4,'0') + '<br>' +
      '<strong>Cashier:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?><br>' +
      '<strong>Date:</strong> <?php echo date('M d, Y h:i A'); ?>'
    );
    $('#receiptModal').modal({backdrop:'static', keyboard:false});
    $('#receiptModal').modal('show');
  <?php endif; ?>

});
</script>

<?php
if(isset($_SESSION['pos_status']) && $_SESSION['pos_status'] != ''):
?>
<script>
Swal.fire({
  icon: '<?php echo $_SESSION['pos_status_code']; ?>',
  title: '<?php echo addslashes($_SESSION['pos_status']); ?>',
  confirmButtonColor: '#3085d6'
});
</script>
<?php
  unset($_SESSION['pos_status']);
  unset($_SESSION['pos_status_code']);
endif;
?>

