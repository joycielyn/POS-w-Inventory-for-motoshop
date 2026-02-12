<?php
include_once 'connectdb.php';
session_start();

include_once "header.php";

if(isset($_POST['btnsave'])) {
    $supplier = $_POST['txtsupplier'];
    $contact = $_POST['txtcontact']; 
   
    if(empty($supplier)) {
        $_SESSION['status'] = "Supplier field is empty";
        $_SESSION['status_code'] = "warning";
    } elseif(strlen($supplier) > 50) { 
        $_SESSION['status'] = "Supplier name should not exceed 50 characters";
        $_SESSION['status_code'] = "warning";
    } else {
        $checkSupplier = $pdo->prepare("SELECT * FROM tbl_supplier WHERE supplier = :sup");
        $checkSupplier->bindParam(':sup', $supplier);
        $checkSupplier->execute();
        $existingSupplier = $checkSupplier->fetch(PDO::FETCH_ASSOC);
        
        if($existingSupplier) {
            $_SESSION['status'] = "Supplier already exists";
            $_SESSION['status_code'] = "warning";
        } else {
            $insert = $pdo->prepare("INSERT INTO tbl_supplier (supplier, contact) VALUES (:sup, :contact)");
            $insert->bindParam(':sup', $supplier);
            $insert->bindParam(':contact', $contact);
            if ($insert->execute()) {
                $_SESSION['status'] = "Supplier added into the database";
                $_SESSION['status_code'] = "success";
            } else {
                $_SESSION['status'] = "Error adding Supplier into the database";
                $_SESSION['status_code'] = "error";
            }
        }
    }
}

if(isset($_POST['btnupdate'])) {
    $supplier = $_POST['txtsupplier'];
    $contact = $_POST['txtcontact'];
    $id = $_POST['txtsupid'];
    if(empty($supplier)) {
        $_SESSION['status'] = "Supplier field is empty";
        $_SESSION['status_code'] = "warning";
    } else {
        $update = $pdo->prepare("UPDATE tbl_supplier SET supplier = :sup, contact = :contact WHERE supid = :id");
        $update->bindParam(':sup', $supplier);
        $update->bindParam(':contact', $contact);
        $update->bindParam(':id', $id);
        if ($update->execute()) {
            $_SESSION['status'] = "Supplier updated successfully";
            $_SESSION['status_code'] = "success";
        } else {
            $_SESSION['status'] = "Supplier not updated successfully";
            $_SESSION['status_code'] = "error";
        }
    }
}

if(isset($_POST['btndelete'])) {
    $delete = $pdo->prepare("DELETE FROM tbl_supplier WHERE supid=:supid");
    $delete->bindParam(':supid', $_POST['btndelete']);
    if($delete->execute()) {
        $_SESSION['status'] = "Deleted successfully";
        $_SESSION['status_code'] = "success";
    } else {
        $_SESSION['status'] = "Deleted not successfully";
        $_SESSION['status_code'] = "error";
    }
}
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Supplier</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">

                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h5 class="m-0">Supplier Form</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <div class="row">
                            <?php
                            if(isset($_POST['btnedit'])) {
                                $select = $pdo->prepare("SELECT * FROM tbl_supplier WHERE supid = :supid");
                                $select->bindParam(':supid', $_POST['btnedit']);
                                $select->execute();
                                if($select) {
                                    $row = $select->fetch(PDO::FETCH_OBJ);
                                    echo '<div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Supplier</label>
                                            <input type="hidden" class="form-control" placeholder="Enter Supplier" value="'.$row->supid.'" name="txtsupid">
                                            <input type="text" class="form-control" placeholder="Enter Supplier" value="'.$row->supplier.'" name="txtsupplier">
                                            <input type="text" class="form-control" placeholder="Enter Contact Number" value="'.$row->contact.'" name="txtcontact">
                                        </div>
                                       
                                        <div class="card-footer">
                                            <button type="submit" class="btn btn-info" name="btnupdate">Update</button>
                                        </div>
                                    </div>';
                                }
                            } else {
                                echo '<div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Supplier</label>
                                            <input type="text" class="form-control" placeholder="Enter Supplier" name="txtsupplier">
                                        </div>
                    
                                        <label for="exampleInputEmail1">Contact Number</label>
                                        <input type="text" class="form-control" placeholder="Enter Contact Number" name="txtcontact">
                                   
                                    
                                        <div class="card-footer">
                                            <button type="submit" class="btn btn-warning" name="btnsave">Save</button>
                                        </div>
                                        
                                    </div>';
                            }
                            ?>
                            <div class="col-md-8">
                                <table id="table_supplier" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <td>#</td>
                                            <td>Supplier</td>
                                            <td>Contact Number</td>
                                            <td>Edit</td>
                                            <td>Delete</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $select = $pdo->prepare("SELECT * FROM tbl_supplier ORDER BY supid ASC");  
                                        $select->execute();
                                        while($row = $select->fetch(PDO::FETCH_OBJ)) {
                                            echo '
                                                <tr>
                                                    <td>'.$row->supid.'</td>
                                                    <td>'.$row->supplier.'</td>
                                                    <td>' . $row->contact . '</td>

                                                    <td>
                                                        <button type="submit" class="btn btn-primary btn-edit" value="'.$row->supid.'" name="btnedit">Edit</button>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-delete" value="'.$row->supid.'" name="btndelete">Delete</

                                                </tr>';
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td>#</td>
                                            <td>Supplier</td>
                                            <td>Contact Number</td>
                                            <td>Edit</td>
                                            <td>Delete</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php
include_once "footer.php";
if(isset($_SESSION['status']) && $_SESSION['status']!='') {
    echo "<script>
        Swal.fire({
            icon: '".$_SESSION['status_code']."',
            title: '".$_SESSION['status']."'
        });
    </script>";
    unset($_SESSION['status']);
}
?>
<script>
    $(document).ready(function() {
        $('.btn-delete').click(function() {
            var supId = $(this).val();
            Swal.fire({
                title: 'Are you sure you want to delete this?',
                text: 'you will not be able to recover this supplier!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d63032',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('form').append('<input type="hidden" name="btndelete" value="'+supId+'">').submit();
                }
            });
        });
    });
</script>
<script>
    $(document).ready( function () {
        $('#table_supplier').DataTable();
    });
</script>