<?php

ob_start();

include_once 'connectdb.php';
session_start();

include_once "headeruser.php";

function fill_product($pdo){
  $output='';
  $select=$pdo->prepare("SELECT * FROM tbl_product ORDER BY product ASC");
  $select->execute();
  $result=$select->fetchAll();
  foreach($result as $row){
    $output.='<option value="'.$row['pid'].'">'.$row['product'].'</option>';
  }
  return $output;
}

if (isset($_POST['btnsaveorder'])) {
  $orderdate      = date('Y-m-d');
  $subtotal       = $_POST['txtsubtotal'];
  $discount       = $_POST['txtdiscount'];
  $vat            = $_POST['txtvat'];
  $total          = $_POST['txttotal'];
  $payment_type   = 'Cash'; // Only Cash payment
  $due            = $_POST['txtdue'];
  $paid           = $_POST['txtpaid'];
  
  // Validate payment - paid amount must be >= total
  if(floatval($paid) < floatval($total)){
    $_SESSION['status'] = "Insufficient payment! Amount paid is less than total.";
    $_SESSION['status_code'] = "error";
    header('location:pos.php');
    exit();
  }

  $arr_pid     = $_POST['pid_arr'];
  $arr_barcode = $_POST['barcode_arr'];
  $arr_name    = $_POST['product_arr'];
  $arr_stock   = $_POST['stock_c_arr'];
  $arr_qty     = $_POST['quantity_arr'];
  $arr_price   = $_POST['price_c_arr'];
  $arr_total   = $_POST['saleprice_arr'];

    

  // Insert invoice data into tbl_invoice table
  $insert = $pdo->prepare("
    INSERT INTO tbl_invoice
    (order_date, subtotal, discount, vat, total, payment_type, due, paid)
    VALUES(:order_date, :subtotal, :discount, :vat, :total, :payment_type, :due, :paid)
  ");
  $insert->bindParam(':order_date',   $orderdate);
  $insert->bindParam(':subtotal',     $subtotal);
  $insert->bindParam(':discount',     $discount);
  $insert->bindParam(':vat',          $vat);
  $insert->bindParam(':total',        $total);
  $insert->bindParam(':payment_type', $payment_type);
  $insert->bindParam(':due',          $due);
  $insert->bindParam(':paid',         $paid);
  $insert->execute();

  $invoice_id = $pdo->lastInsertId();

  if($invoice_id != null){
    // Process invoice details and update stock
    for($i = 0; $i < count($arr_pid); $i++){
      $rem_qty = $arr_stock[$i] - $arr_qty[$i];
      if($rem_qty < 0){
        echo "Order is not completed"; // Handle this case appropriately
      }else{
        $update = $pdo->prepare("UPDATE tbl_product SET stock = :rem_qty WHERE pid = :pid");
        $update->bindParam(':rem_qty', $rem_qty);
        $update->bindParam(':pid', $arr_pid[$i]);
        $update->execute();
      }
      
      // Insert invoice details into tbl_invoice_details table
      $insert_detail = $pdo->prepare("INSERT INTO tbl_invoice_details (invoice_id, barcode, product_id, product_name, qty, rate, saleprice, order_date) VALUES (:invid, :barcode, :pid, :name, :qty, :rate, :saleprice, :order_date)");
      $insert_detail->bindParam(':invid', $invoice_id);
      $insert_detail->bindParam(':barcode', $arr_barcode[$i]);
      $insert_detail->bindParam(':pid', $arr_pid[$i]);
      $insert_detail->bindParam(':name', $arr_name[$i]);
      $insert_detail->bindParam(':qty', $arr_qty[$i]);
      $insert_detail->bindParam(':rate', $arr_price[$i]);
      $insert_detail->bindParam(':saleprice', $arr_total[$i]);
      $insert_detail->bindParam(':order_date', $orderdate);
      
      if(!$insert_detail->execute()){
        print_r($insert_detail->errorInfo()); // Print error information if execution fails
      }
    }

    header('location:userorderlist.php');
  }

}

$select = $pdo->prepare("SELECT * FROM tbl_taxdis");
$select->execute();
$row = $select->fetch(PDO::FETCH_OBJ);

ob_end_flush();

?>

<style type="text/css">
  /* ===== POS Modern Design ===== */

  .pos-left-panel {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    padding: 20px;
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
  }

  .pos-panel-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .pos-panel-title i { color: #4361ee; }

  .pos-search-row { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }

  .pos-search-row .input-group-text {
    background: #1a1a2e;
    color: #fff;
    border: none;
    border-radius: 8px 0 0 8px;
  }
  .pos-search-row .form-control {
    border-radius: 0 8px 8px 0 !important;
    border: 1.5px solid #e0e0e0;
    font-size: 14px;
  }
  .pos-search-row .form-control:focus {
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67,97,238,0.12);
  }

  .pos-table-wrap {
    flex: 1;
    overflow-y: auto;
    border-radius: 10px;
    border: 1.5px solid #e8e8f0;
    margin-top: 6px;
  }
  .pos-table-wrap::-webkit-scrollbar { width: 5px; }
  .pos-table-wrap::-webkit-scrollbar-track { background: #f1f1f1; }
  .pos-table-wrap::-webkit-scrollbar-thumb { background: #c0c0d0; border-radius: 4px; }

  #producttable { width: 100%; border-collapse: collapse; margin: 0; }
  #producttable thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #1a1a2e;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 12px 14px;
    border: none;
  }
  #producttable tbody tr { transition: background 0.15s; }
  #producttable tbody tr:hover { background: #f4f6ff; }
  #producttable tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #f0f0f5;
    vertical-align: middle;
    font-size: 14px;
  }
  #producttable tbody tr:last-child td { border-bottom: none; }

  /* ===== Right Panel ===== */
  .pos-right-panel {
    background: #1a1a2e;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.18);
    padding: 22px 20px;
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    color: #fff;
    overflow-y: auto;
  }
  .pos-right-panel::-webkit-scrollbar { width: 4px; }
  .pos-right-panel::-webkit-scrollbar-thumb { background: #3a3a5e; border-radius: 4px; }

  .pos-summary-title {
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .pos-summary-title i { color: #4cc9f0; }

  .summary-block {
    background: #252545;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 10px;
  }
  .summary-label {
    font-size: 11px;
    color: #a0a0c0;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 6px;
  }
  .summary-input {
    background: #1a1a35 !important;
    border: 1.5px solid #3a3a5e !important;
    color: #fff !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    text-align: right;
    padding: 7px 12px !important;
    width: 100%;
    display: block;
  }
  .summary-input:focus {
    border-color: #4361ee !important;
    box-shadow: 0 0 0 3px rgba(67,97,238,0.18) !important;
    outline: none;
  }
  .summary-input[readonly] { opacity: 0.85; cursor: default; }

  .summary-select {
    background: #1a1a35 !important;
    border: 1.5px solid #3a3a5e !important;
    color: #fff !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    padding: 7px 12px !important;
    width: 100%;
  }

  .total-block {
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    border-radius: 12px;
    padding: 14px 16px;
    margin: 10px 0;
    text-align: center;
  }
  .total-block .total-label {
    font-size: 11px;
    color: rgba(255,255,255,0.75);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
  }
  .total-block .total-value {
    font-size: 28px;
    font-weight: 800;
    color: #fff;
    letter-spacing: 1px;
  }

  .payment-type-btn {
    background: #4361ee;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.5px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin-bottom: 10px;
    cursor: default;
  }

  .paid-block {
    background: #1e3a5f;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 10px;
    border: 1.5px solid #2563eb;
  }
  .paid-block .summary-label { color: #7dd3fc; }
  .paid-input {
    background: #0f2744 !important;
    border: 1.5px solid #2563eb !important;
    color: #7dd3fc !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    text-align: right;
  }

  .change-block {
    background: #1b3a2e;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 14px;
    border: 1.5px solid #16a34a;
  }
  .change-block .summary-label { color: #86efac; }
  .change-input {
    background: #0f2a1e !important;
    border: 1.5px solid #16a34a !important;
    color: #86efac !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    text-align: right;
  }

  .btn-save-order {
    background: linear-gradient(135deg, #06d6a0, #0cb87a);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-size: 16px;
    font-weight: 700;
    width: 100%;
    letter-spacing: 0.5px;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(6,214,160,0.35);
    margin-top: auto;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  .btn-save-order:hover {
    background: linear-gradient(135deg, #0cb87a, #06d6a0);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(6,214,160,0.45);
    color: #fff;
  }
  .btn-save-order:active { transform: translateY(0); }

  .divider-line { border: none; border-top: 1px solid #2a2a4a; margin: 10px 0; }

  .two-col { display: flex; gap: 8px; }
  .two-col > div { flex: 1; }
  .two-col-label { font-size: 10px; color: #a0a0c0; margin-bottom: 3px; }

  @media (max-width: 991px) {
    .pos-left-panel, .pos-right-panel { height: auto; margin-bottom: 15px; }
  }
</style>

<!-- Content Wrapper -->
<div class="content-wrapper" style="background:#f0f2f5;">
  <div class="content" style="padding: 15px 15px 0 15px;">
    <div class="container-fluid" style="padding:0;">

      <form action="" method="post">
      <div class="row" style="margin:0;">

        <!-- ===== LEFT PANEL: Product Cart ===== -->
        <div class="col-lg-8 col-md-7" style="padding: 0 8px 0 0;">
          <div class="pos-left-panel">

            <div class="pos-panel-title">
              <i class="fas fa-shopping-cart"></i>
              Point of Sale
              <span style="font-size:12px; font-weight:400; color:#888; margin-left:auto;">
                <i class="fas fa-calendar-alt" style="margin-right:4px;"></i><?php echo date('F d, Y'); ?>
              </span>
            </div>

            <!-- Barcode & Search Row -->
            <div class="pos-search-row">
              <div class="input-group" style="max-width:260px; flex-shrink:0;">
                <div class="input-group-prepend">
                  <span class="input-group-text">
                    <i class="fas fa-barcode"></i>
                  </span>
                </div>
                <input type="text" class="form-control" placeholder="Scan Barcode..." name="txtbarcode" id="txtbarcode_id">
              </div>
              <div style="flex:1;">
                <select class="form-control select2" data-dropdown-css-class="select2-purple" style="width:100%;">
                  <option>Search Product...<?php echo fill_product($pdo); ?></option>
                </select>
              </div>
            </div>

            <!-- Products Table -->
            <div class="pos-table-wrap">
              <table id="producttable" class="table">
                <thead>
                  <tr>
                    <th><i class="fas fa-box" style="margin-right:5px;"></i>Product</th>
                    <th>Unit</th>
                    <th><i class="fas fa-cubes" style="margin-right:4px;"></i>Stock</th>
                    <th><i class="fas fa-tag" style="margin-right:4px;"></i>Price</th>
                    <th style="text-align:center;">QTY</th>
                    <th><i class="fas fa-receipt" style="margin-right:4px;"></i>Total</th>
                    <th style="text-align:center;"><i class="fas fa-trash-alt"></i></th>
                  </tr>
                </thead>
                <tbody class="details" id="itemtable">
                  <tr data-widget="expandable-table" aria-expanded="false"></tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
        <!-- /.LEFT PANEL -->

        <!-- ===== RIGHT PANEL: Order Summary ===== -->
        <div class="col-lg-4 col-md-5" style="padding: 0 0 0 8px;">
          <div class="pos-right-panel">

            <div class="pos-summary-title">
              <i class="fas fa-file-invoice-dollar"></i>
              Order Summary
            </div>

            <!-- Subtotal -->
            <div class="summary-block">
              <span class="summary-label"><i class="fas fa-calculator" style="margin-right:5px; color:#4cc9f0;"></i>Subtotal</span>
              <input type="text" class="summary-input" name="txtsubtotal" id="txtsubtotal_id" readonly placeholder="0.00">
            </div>

            <!-- Discount -->
            <div class="summary-block">
              <span class="summary-label"><i class="fas fa-percent" style="margin-right:5px; color:#f72585;"></i>Discount</span>
              <div class="two-col" style="margin-top:4px;">
                <div>
                  <div class="two-col-label">RATE (%)</div>
                  <input type="text" class="summary-input" name="txtdiscount" id="txtdiscount_p"
                    value="<?php echo isset($row->discount) ? $row->discount : 0; ?>">
                </div>
                <div>
                  <div class="two-col-label">AMOUNT (₱)</div>
                  <input type="text" class="summary-input" id="txtdiscount_n" readonly placeholder="0.00">
                </div>
              </div>
            </div>

            <!-- VAT -->
            <div class="summary-block">
              <span class="summary-label"><i class="fas fa-file-alt" style="margin-right:5px; color:#7209b7;"></i>VAT</span>
              <?php
                $defaultVat = isset($row->tax) ? (float)$row->tax : 0;
                $presetVats = [0, 10, 20, 30, 40, 50];
              ?>
              <div class="two-col" style="margin-top:4px;">
                <div>
                  <div class="two-col-label">RATE (%)</div>
                  <select class="summary-select" id="txtvat_p" name="txtvat_p">
                    <?php if(!in_array($defaultVat, $presetVats, true)): ?>
                      <option value="<?php echo $defaultVat; ?>" selected><?php echo $defaultVat; ?>%</option>
                    <?php endif; ?>
                    <?php foreach($presetVats as $v): ?>
                      <option value="<?php echo $v; ?>" <?php echo ($v == $defaultVat) ? 'selected' : ''; ?>><?php echo $v; ?>%</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <div class="two-col-label">AMOUNT (₱)</div>
                  <input type="text" class="summary-input" id="txtvat_n" name="txtvat" readonly placeholder="0.00">
                </div>
              </div>
            </div>

            <!-- TOTAL -->
            <div class="total-block">
              <div class="total-label">TOTAL AMOUNT</div>
              <div class="total-value">₱ <span id="txttotal_display">0.00</span></div>
              <input type="hidden" name="txttotal" id="txttotal">
            </div>

            <hr class="divider-line">

            <!-- Payment Type -->
            <div class="payment-type-btn">
              <i class="fas fa-money-bill-wave"></i> CASH PAYMENT
              <input type="hidden" name="rb" value="Cash">
            </div>

            <!-- Paid -->
            <div class="paid-block">
              <span class="summary-label"><i class="fas fa-hand-holding-usd" style="margin-right:5px;"></i>Amount Paid (₱)</span>
              <input type="text" class="summary-input paid-input" name="txtpaid" id="txtpaid" placeholder="Enter amount...">
            </div>

            <!-- Change -->
            <div class="change-block">
              <span class="summary-label"><i class="fas fa-coins" style="margin-right:5px;"></i>Change (₱)</span>
              <input type="text" class="summary-input change-input" name="txtdue" id="txtdue" readonly placeholder="0.00">
            </div>

            <!-- Save Button -->
            <button type="submit" class="btn-save-order" name="btnsaveorder" id="btnsaveorder">
              <i class="fas fa-check-circle" style="font-size:18px;"></i>
              SAVE ORDER
            </button>

          </div>
        </div>
        <!-- /.RIGHT PANEL -->

      </div>
      </form>

    </div>
  </div>
</div>
<!-- /.content-wrapper -->


<?php

include_once("footer.php");

?>

<script>
  //Initialize Select2 Elements
  $('.select2').select2()

  //Initialize Select2 Elements
  $('.select2bs4').select2({
    theme: 'bootstrap4'
  })

  var productarr = [];
  $(function() {

    $('#txtbarcode_id').on('change', function() {
      var barcode = $("#txtbarcode_id").val();

      $.ajax({
        url: "getproduct.php",
        method: "get",
        datatype: "json",
        data: {
          id: barcode
        },
        success: function(data) {



          if (jQuery.inArray(data["pid"], productarr) !== -1) {

            var actualqty = parseInt($('#qty_id' + data["pid"]).val()) + 1;
            $('#qty_id' + data["pid"]).val(actualqty);

            var saleprice = parseInt(actualqty) * data["saleprice"];

            $('#saleprice_id' + data["pid"]).html(saleprice);
            $('#saleprice_idd' + data["pid"]).val(saleprice);

            // $("#txtbarcode_id").val("");

            calculate(0, 0);



          } else {

            addrow(data["pid"], data["product"], data["saleprice"], data["stock"], data["barcode"], data["product_unit"]);

            productarr.push(data["pid"]);

            // $("#txtbarcode_id").val("");

            function addrow(pid, product, saleprice, stock, barcode, product_unit) {

              var unit = product_unit ? product_unit : 'pcs';

              var tr = '<tr>' +

              '<input type="hidden" class="form-control barcode" name="barcode_arr[]" id="barcode_id' + barcode + '" value="' +barcode+ '"></td>' +

                '<td style="text-align:left; vertical-align:middle; font-size:17px;"><class="form-control product_c" name="product_arr[]"  <span class="badge badge-dark">' + product + '</span><input type="hidden" class="form-control pid" name="pid_arr[]" value="' + pid + '"><input type="hidden" class="form-control product" name="product_arr[]" value="' + product + '"> </td>' +

                '<td style="text-align:center;vertical-align:middle; font-size:14px;"><span class="badge badge-secondary">' + unit + '</span></td>' +

                '<td style="text-align:left;vertical-align:middle; font-size:17px;"><span class="badge badge-primary stocklbl" name="stock_arr[]" id="stock_id' + pid + '">' + stock + '<span><input type="hidden" class="form-control stock_C" name="stock_c_arr[]" id="stock_idd' + pid + '" value="' + stock + '"></td>' +

                '<td style="text-align:left;vertical-align:middle; font-size:17px;"><span class="badge badge-warning price" name="price_arr[]" id="price_id' + pid + '">' + saleprice + '<span><input type="hidden" class="form-control price_C" name="price_c_arr[]" id="price_idd' + pid + '" value="' + saleprice + '"></td>' +

                '<td><input type="text" class="form-control qty" name="quantity_arr[]" id="qty_id' + pid + '" value="' + 1 + '" size="1"></td>' +

                '<td style="text-align:left; vertical-align:middle; font-size:17px;"><span class="badge badge-success totalamt" name=netamt_arr[]" id="saleprice_id' + pid + '">' + saleprice + '</span><input type="hidden" class="form-control saleprice" name="saleprice_arr[]" id="saleprice_idd' + pid + '" value="' + saleprice + '"></td>' +

                '<td><center><button type="button" name="remove" class="btn btn-danger btn-sm btnremove" data-id="' + pid + '"><span class="fas fa-trash"></span></center></td>' +


                '</tr>';

              $('.details').append(tr);
              calculate(0, 0);



            }
$("#txtbarcode_id").val("");


          }




        }
      })

    })
  });


  //search product 


  var productarr = [];
  $(function() {

    $('.select2').on('change', function() {
      var productid = $(".select2").val();

      $.ajax({
        url: "getproduct.php",
        method: "get",
        datatype: "json",
        data: {
          id: productid
        },
        success: function(data) {



          if (jQuery.inArray(data["pid"], productarr) !== -1) {

            var actualqty = parseInt($('#qty_id' + data["pid"]).val()) + 1;
            $('#qty_id' + data["pid"]).val(actualqty);

            var saleprice = parseInt(actualqty) * data["saleprice"];

            $('#saleprice_id' + data["pid"]).html(saleprice);
            $('#saleprice_idd' + data["pid"]).val(saleprice);

            // $("#txtbarcode_id").val("");

            calculate(0, 0);
          } else {


            addrow(data["pid"], data["product"], data["saleprice"], data["stock"], data["barcode"], data["product_unit"]);

            productarr.push(data["pid"]);

            // $("#txtbarcode_id").val("");

            function addrow(pid, product, saleprice, stock, barcode, product_unit) {

              var unit = product_unit ? product_unit : 'pcs';

              var tr = '<tr>' +

              '<input type="hidden" class="form-control barcode" name="barcode_arr[]" id="barcode_id' + barcode + '" value="' +barcode+ '">' +

                '<td style="text-align:left; vertical-align:middle; font-size:17px;"><class="form-control product_c" name="product_arr[]" <span class="badge badge-dark">' + product + '</span><input type="hidden" class="form-control pid" name="pid_arr[]" value="' + pid + '"><input type="hidden" class="form-control product" name="product_arr[]" value="' + product + '"> </td>' +

                '<td style="text-align:center;vertical-align:middle; font-size:14px;"><span class="badge badge-secondary">' + unit + '</span></td>' +

                '<td style="text-align:left;vertical-align:middle; font-size:17px;"><span class="badge badge-primary stocklbl" name="stock_arr[]" id="stock_id' + pid + '">' + stock + '<span><input type="hidden" class="form-control stock_C" name="stock_c_arr[]" id="stock_idd' + pid + '" value="' + stock + '"></td>' +

                '<td style="text-align:left;vertical-align:middle; font-size:17px;"><span class="badge badge-warning price" name="price_arr[]" id="price_id' + pid + '">' + saleprice + '<span><input type="hidden" class="form-control price_C" name="price_c_arr[]" id="price_idd' + pid + '" value="' + saleprice + '"></td>' +

                '<td><input type="text" class="form-control qty" name="quantity_arr[]" id="qty_id' + pid + '" value="' + 1 + '" size="1"></td>' +

                '<td style="text-align:left; vertical-align:middle; font-size:17px;"><span class="badge badge-success totalamt" name=netamt_arr[]" id="saleprice_id' + pid + '">' + saleprice + '</span><input type="hidden" class="form-control saleprice" name="saleprice_arr[]" id="saleprice_idd' + pid + '" value="' + saleprice + '"></td>' +

                '<td><center><button type="button" name="remove" class="btn btn-danger btn-sm btnremove" data-id="' + pid + '"><span class="fas fa-trash"></span></center></td>' +


                '</tr>';

              $('.details').append(tr);

              calculate(0, 0);

            }

            $("#txtbarcode_id").val("");

          }




        }
      })

    })
  });


  $("#itemtable").delegate(".qty", "keyup change", function() {

    var quantity = $(this);
    var tr = $(this).parent().parent();

    if ((quantity.val() - 0) > (tr.find(".stock_C").val() - 0)) {

      Swal.fire("WARNING!", "SORRY! this much of quantity is NOT Available", "warning");

      quantity.val(1);

      tr.find(".totalamt").text(quantity.val() * tr.find(".price").text());

      tr.find(".saleprice").val(quantity.val() * tr.find(".price").text());
      calculate(0, 0);

    } else {

      tr.find(".totalamt").text(quantity.val() * tr.find(".price").text());

      tr.find(".saleprice").val(quantity.val() * tr.find(".price").text());
      calculate(0, 0);
    }

  });


  function calculate(dis, paid) {

    var subtotal = 0;
    var paid_amt = paid;

    $(".saleprice").each(function() {
      subtotal = subtotal + $(this).val() * 1;
    });

    $("#txtsubtotal_id").val(subtotal.toFixed(2));

    var discountPct = parseFloat($("#txtdiscount_p").val()) || 0;
    var discountAmt = (discountPct / 100) * subtotal;
    $("#txtdiscount_n").val(discountAmt.toFixed(2));

    var afterDiscount = subtotal - discountAmt;

    var vatPct = parseFloat($("#txtvat_p").val()) || 0;
    // VAT is DISPLAY-ONLY (included in the amount), so subtotal/total stay the same.
    // Extract VAT from the amount: vat = amount - amount/(1+rate)
    var vatRate = vatPct / 100;
    var vatAmt = vatRate > 0 ? (afterDiscount - (afterDiscount / (1 + vatRate))) : 0;
    $("#txtvat_n").val(vatAmt.toFixed(2));

    var total = afterDiscount;
    var due   = total - paid_amt;

    $("#txttotal").val(total.toFixed(2));
    // Update display span
    $("#txttotal_display").text(total.toFixed(2));
    $("#txtdue").val(due.toFixed(2));

  } //calculate function


  $("#txtdiscount_p").keyup(function() {
    calculate($(this).val(), 0);
  });

  $("#txtvat_p").on('change', function() {
    var paid = parseFloat($("#txtpaid").val()) || 0;
    calculate($("#txtdiscount_p").val(), paid);
  });

  $("#txtpaid").keyup(function() {
    var paid = $(this).val();
    var discount = $("#txtdiscount_p").val();
    calculate(discount, paid);
  });


  $(document).on('click', '.btnremove', function() {

    var removed = $(this).attr("data-id");
    productarr = jQuery.grep(productarr, function(value) {

      return value != removed;

    });

    $(this).closest('tr').remove();
    calculate(0, 0);

  });

  // Validate payment before submitting
  $('form').on('submit', function(e) {
    var total = parseFloat($('#txttotal').val()) || 0;
    var paid = parseFloat($('#txtpaid').val()) || 0;
    
    if(paid < total) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Insufficient Payment!',
        text: 'Amount paid (₱' + paid.toFixed(2) + ') is less than total (₱' + total.toFixed(2) + ')',
        confirmButtonColor: '#d33'
      });
      return false;
    }
    
    if($('.details tr').length <= 1) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'No Products!',
        text: 'Please add products to the order',
        confirmButtonColor: '#f39c12'
      });
      return false;
    }
  });
</script>

<?php if(isset($_SESSION['status']) && $_SESSION['status'] != ''): ?>
<script>
  Swal.fire({
    icon: '<?php echo $_SESSION['status_code']; ?>',
    title: '<?php echo $_SESSION['status']; ?>',
    showConfirmButton: true
  });
</script>
<?php 
  unset($_SESSION['status']);
  unset($_SESSION['status_code']);
endif; 
?>
