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

  try { pos_ensure_product_archive_columns($pdo); } catch(Exception $e) {}

  $upd = $pdo->prepare("UPDATE tbl_product SET is_archived = 0, archived_at = NULL WHERE pid = :pid");
  $upd->bindValue(':pid', $id, PDO::PARAM_INT);

  if($upd->execute()){
    echo 'success';
  } else {
    echo 'Failed to restore';
  }

} catch(Exception $e){
  echo 'Error';
}
