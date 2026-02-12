<?php
include_once 'connectdb.php';
session_start();

if($_SESSION['useremail']==""){
    header('location:../index.php');
}

include_once "header.php";

// ================= ADD PRODUCT LOGIC =================
if(isset($_POST['btnsave'])){

    $barcode = $_POST['txtbarcode'];
    $product = $_POST['txtproductname'];
    $category = $_POST['txtselect_option'];
    $description = $_POST['txtdescription'];
    $stock = $_POST['txtstock'];
    $purchaseprice = $_POST['txtpurchaseprice'];
    $saleprice = $_POST['txtsaleprice'];
    $product_unit = $_POST['txtproduct_unit'];

    $f_name = $_FILES['myfile']['name'];
    $f_tmp = $_FILES['myfile']['tmp_name'];
    $f_size = $_FILES['myfile']['size'];
    $f_extension = explode('.', $f_name);
    $f_extension = strtolower(end($f_extension));
    $f_newfile = uniqid().'.'.$f_extension;
    $store = "productimage/".$f_newfile;

    if(in_array($f_extension, ['jpg','jpeg','png','gif'])){

        if($f_size >= 1000000){
            $_SESSION['status'] = "Max file should be 1MB";
            $_SESSION['status_code'] = "error";
        }else{
            if(move_uploaded_file($f_tmp,$store)){
                $productimage = $f_newfile;

                if(empty($barcode)){
                    $insert = $pdo->prepare("INSERT INTO tbl_product 
                        (product, product_unit, category, description, stock, purchaseprice, saleprice, image) 
                        VALUES (:product, :product_unit, :category, :description, :stock, :pprice, :saleprice, :img)");

                    $insert->bindParam(':product', $product);
                    $insert->bindParam(':product_unit', $product_unit);
                    $insert->bindParam(':category', $category);
                    $insert->bindParam(':description', $description);
                    $insert->bindParam(':stock', $stock);
                    $insert->bindParam(':pprice', $purchaseprice);
                    $insert->bindParam(':saleprice', $saleprice);
                    $insert->bindParam(':img', $productimage);
                    $insert->execute();

                    $pid = $pdo->lastInsertId();
                    $newbarcode = $pid . date('his');
                    $update = $pdo->prepare("UPDATE tbl_product SET barcode='$newbarcode' WHERE pid='".$pid."'");
                    $update->execute();

                    $_SESSION['status'] = "Product Inserted Successfully";
                    $_SESSION['status_code'] = "success";

                }else{
                    $insert = $pdo->prepare("INSERT INTO tbl_product 
                        (barcode, product, product_unit, category, description, stock, purchaseprice, saleprice, image) 
                        VALUES (:barcode, :product, :product_unit, :category, :description, :stock, :pprice, :saleprice, :img)");

                    $insert->bindParam(':barcode', $barcode);
                    $insert->bindParam(':product', $product);
                    $insert->bindParam(':product_unit', $product_unit);
                    $insert->bindParam(':category', $category);
                    $insert->bindParam(':description', $description);
                    $insert->bindParam(':stock', $stock);
                    $insert->bindParam(':pprice', $purchaseprice);
                    $insert->bindParam(':saleprice', $saleprice);
                    $insert->bindParam(':img', $productimage);
                    $insert->execute();

                    $_SESSION['status'] = "Product Inserted Successfully";
                    $_SESSION['status_code'] = "success";
                }

            }
        }

    }else{
        $_SESSION['status'] = "Only jpg, jpeg, png, gif can be uploaded";
        $_SESSION['status_code'] = "error";
    }
}
?>

<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card card-primary card-outline">

                        <div class="card-header d-flex justify-content-between">
                            <h5 class="m-0">Product List :</h5>
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addProductModal">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                        </div>

                        <div class="card-body">
                            <table class="table table-striped table-hover" id="table_product">
                                <thead>
                                    <tr>
                                        <td>Barcode</td>
                                        <td>Product</td>
                                        <td>Unit</td>
                                        <td>Category</td>
                                        <td>Description</td>
                                        <td>Stock</td>
                                        <td>Purchase Price</td>
                                        <td>Sale Price</td>
                                        <td>Image</td>
                                        <td>Actions</td>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php
                                $select = $pdo->prepare("SELECT * FROM tbl_product ORDER BY pid ASC");
                                $select->execute();

                                while ($row = $select->fetch(PDO::FETCH_OBJ)) {
                                    echo '<tr>
                                        <td>'.$row->barcode.'</td>
                                        <td>'.$row->product.'</td>
                                        <td><span class="badge badge-info">'.$row->product_unit.'</span></td>
                                        <td>'.$row->category.'</td>
                                        <td>'.$row->description.'</td>';

                                    if($row->stock > 0){
                                        echo '<td><span class="badge badge-success">'.$row->stock.' '.$row->product_unit.'</span></td>';
                                    }else{
                                        echo '<td><span class="badge badge-danger">Out of Stock</span></td>';
                                    }

                                    echo '<td>'.$row->purchaseprice.'</td>
                                        <td>'.$row->saleprice.'</td>
                                        <td><img src="productimage/'.$row->image.'" width="40"></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="printbarcode.php?id='.$row->pid.'" class="btn btn-dark btn-xs">
                                                    <span class="fa fa-barcode"></span>
                                                </a>
                                                <a href="viewproduct.php?id='.$row->pid.'" class="btn btn-warning btn-xs">
                                                    <span class="fa fa-eye"></span>
                                                </a>
                                                <a href="editproduct.php?id='.$row->pid.'" class="btn btn-success btn-xs">
                                                    <span class="fa fa-edit"></span>
                                                </a>
                                                <button id='.$row->pid.' class="btn btn-danger btn-xs btndelete">
                                                    <span class="fa fa-trash"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ADD PRODUCT MODAL ================= -->
<div class="modal fade" id="addProductModal">
<div class="modal-dialog modal-lg">
<div class="modal-content">
    <form action="" method="post" enctype="multipart/form-data">
        <div class="modal-header bg-primary">
            <h5 class="modal-title">Add Product</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Barcode</label>
                        <input type="text" class="form-control" name="txtbarcode">
                    </div>

                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" class="form-control" name="txtproductname" required>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="txtselect_option" required>
                            <option disabled selected>Select Category</option>
                            <?php
                            $select=$pdo->prepare("select * from tbl_category order by catid desc");
                            $select->execute();
                            while($row=$select->fetch(PDO::FETCH_ASSOC)){
                                echo '<option>'.$row['category'].'</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="txtdescription" rows="3"></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" class="form-control" name="txtstock" min="1" step="any" required>
                    </div>

                    <div class="form-group">
                        <label>Purchase Price</label>
                        <input type="number" class="form-control" name="txtpurchaseprice" min="1" step="any" required>
                    </div>

                    <div class="form-group">
                        <label>Sale Price</label>
                        <input type="number" class="form-control" name="txtsaleprice" min="1" step="any" required>
                    </div>

                    <div class="form-group">
                        <label>Product Unit</label>
                        <select class="form-control" name="txtproduct_unit" required>
                            <option value="" disabled selected>Select Unit</option>
                            <option value="pcs">PCS</option>
                            <option value="set">SET</option>
                            <option value="box">BOX</option>
                            <option value="pack">PACK</option>
                            <option value="kg">KG</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" class="form-control" name="myfile" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" name="btnsave">Save Product</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
    </form>
</div>
</div>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function () {

    $('#table_product').DataTable();

    $('.btndelete').click(function(){
        var tdt=$(this);
        var id=$(this).attr('id');

        Swal.fire({
            title:"Delete this product?",
            icon:"warning",
            showCancelButton:true
        }).then((result)=>{
            if(result.isConfirmed){
                $.ajax({
                    url:'productdelete.php',
                    type:'post',
                    data:{pidd:id},
                    success:function(){
                        tdt.parents('tr').hide();
                    }
                });

                Swal.fire("Deleted!","","success");
            }
        });
    });

    <?php if(isset($_SESSION['status']) && $_SESSION['status']!=''){ ?>
        Swal.fire({
            icon: '<?php echo $_SESSION['status_code'];?>',
            title: '<?php echo $_SESSION['status'];?>'
        });
    <?php unset($_SESSION['status']); } ?>

});
</script>
