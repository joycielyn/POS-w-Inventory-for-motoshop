<?php
include_once 'connectdb.php';
session_start();

include_once "header.php";
if(isset($_POST['btnsave'])) {
    $sgst = $_POST['txtsgst'];
    $cgst = $_POST['txtcgst'];
    $discount = $_POST['txtdiscount'];
  

 
    if(empty($sgst)) {
        $_SESSION['status'] = " field is empty";
        $_SESSION['status_code'] = "warning";
    } 
   
         else {
          
            $insert = $pdo->prepare("INSERT INTO tbl_taxdis (sgst, cgst, discount) VALUES (:sgst, :cgst, :discount)");
            $insert->bindParam(':sgst', $sgst);
            $insert->bindParam(':cgst', $cgst);
            $insert->bindParam(':discount', $discount);

            
            if ($insert->execute()) {
                $_SESSION['status'] = "Tax and Discount are added Successfully!";
                $_SESSION['status_code'] = "success";
            } else {
                $_SESSION['status'] = "FAILED!";
                $_SESSION['status_code'] = "error";
            }
        }
    
    }

    if(isset($_POST['btnupdate'])) {
    
        $sgst = $_POST['txtsgst'];
        $cgst = $_POST['txtcgst'];
        $discount = $_POST['txtdiscount'];
      
        $id = $_POST['txtid'];
        if(empty($sgst)) {
            $_SESSION['status'] = "SGST field is empty";
            $_SESSION['status_code'] = "warning";
        } else {
            $update = $pdo->prepare("UPDATE tbl_taxdis SET sgst=:sgst, cgst=:cgst, discount=:disc WHERE taxdis_id=".$id);
            $update->bindParam(':sgst', $sgst);
            $update->bindParam(':cgst', $cgst);
            $update->bindParam(':disc', $discount);
    
            if ($update->execute()) {
                $_SESSION['status'] = "Tax and Discount updated Successfully";
                $_SESSION['status_code'] = "success";
            } else {
                $_SESSION['status'] = "Failed to update tax and Discount";
                $_SESSION['status_code'] = "error";
            }
        }
    }
    


// if(isset($_POST['btndelete'])) {
//     $delete=$pdo->prepare("DELETE FROM tbl_taxdis WHERE taxdis_id=:taxdis_id");
//     $delete->bindParam(':taxdis_id', $_POST['btndelete']);
//     if($delete->execute()) {
//         $_SESSION['status'] = "Deleted successfully";
//         $_SESSION['status_code'] = "success";
//     } else {
//         $_SESSION['status'] = "Deleted not successfully";
//         $_SESSION['status_code'] = "error";
//     }
// }
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">TAX AND DISCOUNT</h1>
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
                    <h5 class="m-0">Text  and Discount Form</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <div class="row">
                            <?php
                            if(isset($_POST['btnedit'])) {
                                $select=$pdo->prepare("SELECT * FROM tbl_taxdis WHERE taxdis_id = :catid");
                                $select->bindParam(':catid', $_POST['btnedit']);
                                $select->execute();
                                if($select) {
                                    $row=$select->fetch(PDO::FETCH_OBJ);
                                    echo '<div class="col-mg-4">

                                        <div class="form-group">
                                          
                                            <input type="hidden" class="form-control" placeholder="Enter Category" value="'.$row->taxdis_id.'" name="txtid">
                                            
                                        </div>


                                        <div class="form-group">
                                        <label for="exampleInputEmail1">SGST(%) </label>
                                        <input type="text" class="form-control" placeholder="Enter SGST"  value="'.$row->sgst.'" name="txtsgst">
                                    </div>

                                    <div class="form-group">
                                        <label for="exampleInputEmail1">CGST(%)</label>
                                        <input type="text" class="form-control" placeholder="Enter CGST " value="'.$row->cgst.'"  name="txtcgst">
                                    </div>

                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Discount (%)</label>
                                        <input type="text" class="form-control" placeholder="Enter Discount"   value="'.$row->discount.'" name="txtdiscount">
                                    </div>

 
                                        

                                        
                                        <div class="card-footer">
                                            <button type="submit" class="btn btn-info" name="btnupdate">Update</button>
                                        </div>
                                    </div>';
                                }
                            } else {
                                echo '<div class="col-mg-4">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">SGST(%) </label>
                                            <input type="text" class="form-control" placeholder="Enter SGST" name="txtsgst">
                                        </div>

                                        <div class="form-group">
                                            <label for="exampleInputEmail1">CGST(%)</label>
                                            <input type="text" class="form-control" placeholder="Enter CGST " name="txtcgst">
                                        </div>

                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Discount (%)</label>
                                            <input type="text" class="form-control" placeholder="Enter Discount" name="txtdiscount">
                                        </div>

                                        <div class="card-footer">
                                            <button type="submit" class="btn btn-warning" name="btnsave">Save</button>
                                        </div>
                                    </div>';
                            }
                            ?>
                            <div class="col-md-8">
                                <table id="table_tax" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <td>#</td>
                                            <td>SGST</td>
                                            <td>CGST</td>
                                            <td>Discount</td>
                                            <td>Edit</td>
                                            <!-- <td>Delete</td> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $select = $pdo->prepare("SELECT * FROM tbl_taxdis ORDER BY taxdis_id ASC");  
                                        $select->execute();
                                        while($row=$select->fetch(PDO::FETCH_OBJ)) {
                                            echo '
                                                <tr>
                                                    <td>'.$row->taxdis_id.'</td>
                                                    <td>'.$row->sgst.'</td>
                                                    <td>'.$row->cgst.'</td>
                                                    <td>'.$row->discount.'</td>


                                                    <td>
                                                        <button type="submit" class="btn btn-primary btn-edit" value="'.$row->taxdis_id.'" name="btnedit">Edit</button>
                                                    </td>
                                                
                                                </tr>';
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                             <td>#</td>
                                            <td>SGST</td>
                                            <td>CGST</td>
                                            <td>Discount</td>
                                            <td>Edit</td>
                                            <!-- <td>Delete</td> -->
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
            var catId = $(this).val();
            Swal.fire({
                title: 'Confirmation',
                text: 'Are you sure you want to delete this? Once deleted, you will not be able to recover this category!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d63032',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('form').append('<input type="hidden" name="btndelete" value="'+catId+'">').submit();
                }
            });
        });
    });
</script>
<script>
    $(document).ready( function () {
        $('#table_tax').DataTable();
    });
</script>