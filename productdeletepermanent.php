<?php
include_once 'connectdb.php';
session_start();

if(($_SESSION['useremail'] ?? '') === '' || ($_SESSION['role'] ?? '') !== 'Admin'){
  echo 'Unauthorized';
  exit;
}

try {
  if(!isset($_POST['pidd'])){
    echo 'Invalid request';
    exit;
  }
  $id = (int)$_POST['pidd'];
  if($id <= 0){
    echo 'Invalid product';
    exit;
  }

  // Block permanent delete if referenced by invoice details
  try {
    $chk = $pdo->prepare("SELECT COUNT(*) as c FROM tbl_invoice_details WHERE product_id = :pid");
    $chk->bindValue(':pid', $id, PDO::PARAM_INT);
    $chk->execute();
    $cnt = (int)($chk->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    if($cnt > 0){
      echo 'Cannot delete. Product is used in invoices.';
      exit;
    }
  } catch(Exception $e) {
    // if check fails, fail-safe
    echo 'Cannot delete right now.';
    exit;
  }

  // get image
  $img = '';
  try {
    $s = $pdo->prepare("SELECT image FROM tbl_product WHERE pid = :pid LIMIT 1");
    $s->bindValue(':pid', $id, PDO::PARAM_INT);
    $s->execute();
    $img = (string)($s->fetch(PDO::FETCH_ASSOC)['image'] ?? '');
  } catch(Exception $e) {}

  $del = $pdo->prepare("DELETE FROM tbl_product WHERE pid = :pid");
  $del->bindValue(':pid', $id, PDO::PARAM_INT);

  if($del->execute()){
    if($img !== ''){
      $path = __DIR__ . '/productimage/' . basename($img);
      if(file_exists($path)){
        @unlink($path);
      }
    }
    echo 'success';
  } else {
    echo 'Failed to delete';
  }

} catch(Exception $e){
  echo 'Error';
}
