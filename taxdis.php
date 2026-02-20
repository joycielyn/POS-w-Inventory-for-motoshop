<?php
include_once 'connectdb.php';
session_start();

if($_SESSION['useremail']==""){
    header('location:../index.php');
}

include_once "header.php";

/* ================= SAVE ================= */
if(isset($_POST['btnsave'])){

    $tax = $_POST['txttax'];
    $discount = $_POST['txtdiscount'];

    if(empty($tax)){
        $_SESSION['status'] = "Tax field is empty";
        $_SESSION['status_code'] = "warning";
    }else{

        $insert = $pdo->prepare("INSERT INTO tbl_taxdis (tax, discount) 
                                 VALUES (:tax, :discount)");

        $insert->bindParam(':tax',$tax);
        $insert->bindParam(':discount',$discount);

        if($insert->execute()){
            $_SESSION['status'] = "Tax and Discount Added Successfully";
            $_SESSION['status_code'] = "success";
        }else{
            $_SESSION['status'] = "Failed to Add";
            $_SESSION['status_code'] = "error";
        }
    }
}

/* ================= UPDATE ================= */
if(isset($_POST['btnupdate'])){

    $tax = $_POST['txttax'];
    $discount = $_POST['txtdiscount'];
    $id = $_POST['txtid'];

    if(empty($tax)){
        $_SESSION['status'] = "Tax field is empty";
        $_SESSION['status_code'] = "warning";
    }else{

        $update = $pdo->prepare("UPDATE tbl_taxdis 
                                 SET tax=:tax, discount=:discount 
                                 WHERE taxdis_id=:id");

        $update->bindParam(':tax',$tax);
        $update->bindParam(':discount',$discount);
        $update->bindParam(':id',$id);

        if($update->execute()){
            $_SESSION['status'] = "Updated Successfully";
            $_SESSION['status_code'] = "success";
        }else{
            $_SESSION['status'] = "Update Failed";
            $_SESSION['status_code'] = "error";
        }
    }
}
?>

<!-- Content Wrapper -->
<div class="content-wrapper">

<div class="content-header">
<div class="container-fluid">
<div class="row mb-2">
<div class="col-sm-6">
<h1 class="m-0">TAX AND DISCOUNT</h1>
</div>
</div>
</div>
</div>

<div class="content">
<div class="container-fluid">

<div class="card card-warning card-outline">
<div class="card-header">
<h5 class="m-0">Tax and Discount Form</h5>
</div>

<div class="card-body">
<form action="" method="post">
<div class="row">

<?php
/* ================= EDIT MODE ================= */
if(isset($_POST['btnedit'])){

    $select = $pdo->prepare("SELECT * FROM tbl_taxdis WHERE taxdis_id=:id");
    $select->bindParam(':id',$_POST['btnedit']);
    $select->execute();
    $row=$select->fetch(PDO::FETCH_OBJ);
?>

<div class="col-md-4">

<input type="hidden" name="txtid" value="<?php echo $row->taxdis_id; ?>">

<div class="form-group">
<label>Tax (%)</label>
<input type="number" step="0.01" class="form-control" name="txttax" value="<?php echo $row->tax; ?>" required>
</div>

<div class="form-group">
<label>Discount (%)</label>
<input type="number" step="0.01" class="form-control" name="txtdiscount" value="<?php echo $row->discount; ?>">
</div>

<button type="submit" class="btn btn-info" name="btnupdate">Update</button>

</div>

<?php
}else{
?>

<div class="col-md-4">

<div class="form-group">
<label>Tax (%)</label>
<input type="number" step="0.01" class="form-control" name="txttax" required>
</div>

<div class="form-group">
<label>Discount (%)</label>
<input type="number" step="0.01" class="form-control" name="txtdiscount">
</div>

<button type="submit" class="btn btn-warning" name="btnsave">Save</button>

</div>

<?php } ?>

<!-- ================= TABLE ================= -->
<div class="col-md-8">

<table id="table_tax" class="table table-striped table-hover">
<thead>
<tr>
<td>ID</td>
<td>Tax (%)</td>
<td>Discount (%)</td>
<td>Edit</td>
</tr>
</thead>

<tbody>

<?php
$select=$pdo->prepare("SELECT * FROM tbl_taxdis ORDER BY taxdis_id ASC");
$select->execute();

while($row=$select->fetch(PDO::FETCH_OBJ)){
echo '
<tr>
<td>'.$row->taxdis_id.'</td>
<td>'.$row->tax.'</td>
<td>'.$row->discount.'</td>
<td>
<button type="submit" class="btn btn-primary" 
value="'.$row->taxdis_id.'" name="btnedit">Edit</button>
</td>
</tr>';
}
?>

</tbody>
</table>

</div>
</div>
</form>
</div>
</div>

</div>
</div>
</div>

<?php
include_once "footer.php";

/* ================= ALERT ================= */
if(isset($_SESSION['status']) && $_SESSION['status']!=''){
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
$(document).ready(function () {
$('#table_tax').DataTable();
});
</script>
