<?php 

require('./fpdf/fpdf.php');
include_once'connectdb.php';

$id = $_GET["id"];

$select = $pdo->prepare("SELECT * FROM tbl_invoice WHERE invoice_id = :id");
$select->bindParam(':id',$id);
$select->execute();
$row = $select->fetch(PDO::FETCH_OBJ);

$pdf= new FPDF('p','mm', array(80,200));
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', '14');
$pdf->Cell(60,8,'Concepcion Motorshop', 1,1,'C');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(60,5,'PHONE NUMBER : 09620433464',0,1,'C');
$pdf->Cell(60,5,'WEBSITE : concepcionmotorshop@gmail.com',0,1,'C');

$pdf->Line(7,28,72,28);
$pdf->Ln(2);

$pdf->SetFont('Courier','B',8);
$pdf->Cell(30,4,'Invoice No: ',0,0);
$pdf->Cell(30,4,$row->invoice_id,0,1);

$pdf->Cell(30,4,'Date: ',0,0);
$pdf->Cell(30,4,$row->order_date,0,1);

$pdf->Ln(3);

$pdf->SetX(7);
$pdf->SetFont('Courier','B',8);
$pdf->Cell(34,5,'PRODUCT',1,0,'C');
$pdf->Cell(7,5,'QTY',1,0,'C');
$pdf->Cell(12,5,'PRC',1,0,'C');
$pdf->Cell(12,5,'TOTAL',1,1,'C');

$select = $pdo->prepare("SELECT * FROM tbl_invoice_details WHERE invoice_id = :id");
$select->bindParam(':id',$id);
$select->execute();

while($product=$select->fetch(PDO::FETCH_OBJ)){

$pdf->SetX(7);
$pdf->SetFont('Helvetica','',8);
$pdf->Cell(34,5,$product->product_name,1,0,'L');
$pdf->Cell(7,5,$product->qty,1,0,'C');
$pdf->Cell(12,5,$product->rate,1,0,'C');
$pdf->Cell(12,5,$product->rate*$product->qty,1,1,'C');
}

$pdf->Ln(2);

/* ================= SUBTOTAL ================= */
$pdf->SetX(7);
$pdf->SetFont('Courier','B',8);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'SUBTOTAL',1,0,'C');
$pdf->Cell(20,5,$row->subtotal,1,1,'C');

/* ================= DISCOUNT % ================= */
$pdf->SetX(7);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'DISCOUNT %',1,0,'C');
$pdf->Cell(20,5,$row->discount,1,1,'C');

$discount_amount = ($row->discount/100) * $row->subtotal;

/* ================= DISCOUNT $ ================= */
$pdf->SetX(7);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'DISCOUNT ($)',1,0,'C');
$pdf->Cell(20,5,number_format($discount_amount,2),1,1,'C');

/* ================= TAX % ================= */
$pdf->SetX(7);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'TAX %',1,0,'C');
$pdf->Cell(20,5,$row->tax,1,1,'C');

$tax_amount = ($row->tax/100) * $row->subtotal;

/* ================= TAX $ ================= */
$pdf->SetX(7);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'TAX ($)',1,0,'C');
$pdf->Cell(20,5,number_format($tax_amount,2),1,1,'C');

/* ================= GRAND TOTAL ================= */
$pdf->SetX(7);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'G-TOTAL',1,0,'C');
$pdf->Cell(20,5,$row->total,1,1,'C');

/* ================= PAYMENT ================= */
$pdf->SetX(7);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'PAID',1,0,'C');
$pdf->Cell(20,5,$row->paid,1,1,'C');

$pdf->SetX(7);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'DUE',1,0,'C');
$pdf->Cell(20,5,$row->due,1,1,'C');

$pdf->SetX(7);
$pdf->Cell(20,5,'',0,0);
$pdf->Cell(25,5,'PAYMENT TYPE',1,0,'C');
$pdf->Cell(20,5,$row->payment_type,1,1,'C');

$pdf->Ln(5);
$pdf->SetFont('Arial','I',8);
$pdf->Cell(72,5,'Thank you and please come again!!',0,1,'C');

$pdf->Output();
?>
