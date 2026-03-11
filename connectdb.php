<?php

try{

    $pdo = new PDO('mysql:host=localhost;dbname=pos_inventory_db','root','');
    
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

}catch(PDOException $e){

    echo $e->getMessage();


}

/**
 * Best-effort: ensure archive columns exist in tbl_product.
 * Adds:
 * - is_archived TINYINT(1) DEFAULT 0
 * - archived_at DATETIME NULL
 */
if(!function_exists('pos_ensure_product_archive_columns')){
  function pos_ensure_product_archive_columns(PDO $pdo): void {
    try {
      $hasArchived = false;
      $hasArchivedAt = false;

      $chk = $pdo->query("SHOW COLUMNS FROM tbl_product");
      foreach(($chk ? $chk->fetchAll(PDO::FETCH_ASSOC) : []) as $col){
        if(($col['Field'] ?? '') === 'is_archived') $hasArchived = true;
        if(($col['Field'] ?? '') === 'archived_at') $hasArchivedAt = true;
      }

      if(!$hasArchived){
        try { $pdo->exec("ALTER TABLE tbl_product ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
      }
      if(!$hasArchivedAt){
        try { $pdo->exec("ALTER TABLE tbl_product ADD COLUMN archived_at DATETIME NULL"); } catch(Exception $e) {}
      }
    } catch(Exception $e) {
      // ignore
    }
  }
}

//echo'connection success';

?>

