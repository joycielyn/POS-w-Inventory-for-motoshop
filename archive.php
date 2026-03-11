<?php
include_once 'connectdb.php';
session_start();

if(($_SESSION['useremail'] ?? '') === '' || ($_SESSION['role'] ?? '') !== 'Admin'){
  header('location:../index.php');
  exit;
}

// Ensure archive columns exist (best-effort)
try { pos_ensure_product_archive_columns($pdo); } catch(Exception $e) {}

$hasArchivedCol = false;
try {
  $chk = $pdo->query("SHOW COLUMNS FROM tbl_product LIKE 'is_archived'");
  $hasArchivedCol = ($chk && $chk->rowCount() > 0);
} catch(Exception $e) { $hasArchivedCol = false; }

$archived = [];
if($hasArchivedCol){
  try {
    $q = $pdo->prepare("SELECT * FROM tbl_product WHERE is_archived = 1 ORDER BY archived_at DESC, pid DESC");
    $q->execute();
    $archived = $q->fetchAll(PDO::FETCH_ASSOC);
  } catch(Exception $e) {
    $archived = [];
  }
}

include_once 'header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><i class="fas fa-archive mr-2"></i>Archived Products</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
            <li class="breadcrumb-item active">Archived Products</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php if(!$hasArchivedCol): ?>
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          Archive feature is not available yet. Please load `productlist.php` once then try again.
        </div>
      <?php else: ?>

      <div class="card card-outline card-dark">
        <div class="card-header">
          <h3 class="card-title">Archived list</h3>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover" id="table_archived">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Product</th>
                  <th>Category</th>
                  <th class="text-right">Stock</th>
                  <th class="text-right">Sale</th>
                  <th>Archived At</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($archived as $row): ?>
                  <tr data-pid="<?php echo (int)$row['pid']; ?>">
                    <td><?php echo (int)$row['pid']; ?></td>
                    <td>
                      <strong><?php echo htmlspecialchars((string)($row['product'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                      <?php if(!empty($row['barcode'])): ?>
                        <br><small class="text-muted">Barcode: <?php echo htmlspecialchars((string)$row['barcode'], ENT_QUOTES, 'UTF-8'); ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars((string)($row['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-right"><?php echo htmlspecialchars((string)($row['stock'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-right">₱<?php echo number_format((float)($row['saleprice'] ?? 0), 2); ?></td>
                    <td><?php echo htmlspecialchars((string)($row['archived_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-center">
                      <div class="btn-group">
                        <button class="btn btn-sm btn-success btn-restore" data-id="<?php echo (int)$row['pid']; ?>">
                          <i class="fas fa-undo"></i> Restore
                        </button>
                        <button class="btn btn-sm btn-danger btn-delete-perm" data-id="<?php echo (int)$row['pid']; ?>">
                          <i class="fas fa-trash"></i> Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php endif; ?>

    </div>
  </section>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function(){
    if(window.jQuery && jQuery.fn && jQuery.fn.DataTable){
      $('#table_archived').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[5,'desc']]
      });
    }

    function postAction(url, payload){
      return fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
        body: new URLSearchParams(payload).toString()
      }).then(r => r.text());
    }

    document.querySelectorAll('.btn-restore').forEach(function(btn){
      btn.addEventListener('click', function(){
        var id = this.getAttribute('data-id');
        Swal.fire({
          title: 'Restore this product?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, restore',
          cancelButtonText: 'Cancel'
        }).then(function(res){
          if(!res.isConfirmed) return;
          Swal.fire({title:'Restoring...', allowOutsideClick:false, didOpen: () => Swal.showLoading()});
          postAction('productrestore.php', {pidd:id}).then(function(txt){
            if(String(txt).trim() === 'success'){
              var tr = document.querySelector('tr[data-pid="'+id+'"]');
              if(tr) tr.remove();
              Swal.fire({icon:'success', title:'Restored', timer:1200, showConfirmButton:false});
            } else {
              Swal.fire({icon:'error', title:'Error', text: txt || 'Failed to restore'});
            }
          });
        });
      });
    });

    document.querySelectorAll('.btn-delete-perm').forEach(function(btn){
      btn.addEventListener('click', function(){
        var id = this.getAttribute('data-id');
        Swal.fire({
          title: 'Delete permanently?',
          text: "This can't be undone.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Yes, delete',
          cancelButtonText: 'Cancel'
        }).then(function(res){
          if(!res.isConfirmed) return;
          Swal.fire({title:'Deleting...', allowOutsideClick:false, didOpen: () => Swal.showLoading()});
          postAction('productdeletepermanent.php', {pidd:id}).then(function(txt){
            if(String(txt).trim() === 'success'){
              var tr = document.querySelector('tr[data-pid="'+id+'"]');
              if(tr) tr.remove();
              Swal.fire({icon:'success', title:'Deleted', timer:1200, showConfirmButton:false});
            } else {
              Swal.fire({icon:'warning', title:'Cannot delete', text: txt || 'Failed to delete'});
            }
          });
        });
      });
    });
  });
</script>

<?php include_once 'footer.php'; ?>
