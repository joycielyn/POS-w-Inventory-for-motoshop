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
                        // Check if product_unit column exists
                        $checkColumn = $pdo->query("SHOW COLUMNS FROM tbl_product LIKE 'product_unit'");
                        $hasProductUnit = $checkColumn->rowCount() > 0;
                        
                        if($hasProductUnit){
                            $insert = $pdo->prepare("INSERT INTO tbl_product 
                                (product, product_unit, category, description, stock, purchaseprice, saleprice, image) 
                                VALUES (:product, :product_unit, :category, :description, :stock, :pprice, :saleprice, :img)");
                            $insert->bindParam(':product_unit', $product_unit);
                        } else {
                            $insert = $pdo->prepare("INSERT INTO tbl_product 
                                (product, category, description, stock, purchaseprice, saleprice, image) 
                                VALUES (:product, :category, :description, :stock, :pprice, :saleprice, :img)");
                        }

                        $insert->bindParam(':product', $product);
                        $insert->bindParam(':category', $category);
                        $insert->bindParam(':description', $description);
                        $insert->bindParam(':stock', $stock);
                        $insert->bindParam(':pprice', $purchaseprice);
                        $insert->bindParam(':saleprice', $saleprice);
                        $insert->bindParam(':img', $productimage);
                        $insert->execute();

                        $pid = $pdo->lastInsertId();
                        $newbarcode = $pid . date('his');
                        $update = $pdo->prepare("UPDATE tbl_product SET barcode=:barcode WHERE pid=:pid");
                        $update->bindParam(':barcode', $newbarcode);
                        $update->bindParam(':pid', $pid, PDO::PARAM_INT);
                        $update->execute();

                        $_SESSION['status'] = "Product Inserted Successfully";
                        $_SESSION['status_code'] = "success";

                    }else{
                        // Check if product_unit column exists
                        $checkColumn = $pdo->query("SHOW COLUMNS FROM tbl_product LIKE 'product_unit'");
                        $hasProductUnit = $checkColumn->rowCount() > 0;
                        
                        if($hasProductUnit){
                            $insert = $pdo->prepare("INSERT INTO tbl_product 
                                (barcode, product, product_unit, category, description, stock, purchaseprice, saleprice, image) 
                                VALUES (:barcode, :product, :product_unit, :category, :description, :stock, :pprice, :saleprice, :img)");
                            $insert->bindParam(':product_unit', $product_unit);
                        } else {
                            $insert = $pdo->prepare("INSERT INTO tbl_product 
                                (barcode, product, category, description, stock, purchaseprice, saleprice, image) 
                                VALUES (:barcode, :product, :category, :description, :stock, :pprice, :saleprice, :img)");
                        }

                        $insert->bindParam(':barcode', $barcode);
                        $insert->bindParam(':product', $product);
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

    // ================= EDIT PRODUCT LOGIC =================
    if(isset($_POST['btneditproduct'])){
        // Start output buffering to prevent header issues
        ob_start();
        
        try {
            $id = intval($_POST['edit_pid']);
            $product_txt = trim($_POST['txtproductname']);
            $category_txt = trim($_POST['txtselect_option']);
            $description_txt = trim($_POST['txtdescription']);
            $stock_txt = intval($_POST['txtstock']);
            $purchaseprice_txt = floatval($_POST['txtpurchaseprice']);
            $saleprice_txt = floatval($_POST['txtsaleprice']);
            $product_unit_txt = isset($_POST['txtproduct_unit']) ? trim($_POST['txtproduct_unit']) : 'pcs';
            
            // Get current image
            $select_img = $pdo->prepare("SELECT image FROM tbl_product WHERE pid=:pid");
            $select_img->bindParam(':pid', $id, PDO::PARAM_INT);
            $select_img->execute();
            $img_row = $select_img->fetch(PDO::FETCH_ASSOC);
            $current_image = $img_row['image'];

            // Initialize image variable
            $image_to_save = $current_image;
            $upload_success = true;

            // Check if new image is uploaded
            if(isset($_FILES['myfile']) && !empty($_FILES['myfile']['name'])){
                $f_name = $_FILES['myfile']['name'];
                $f_tmp = $_FILES['myfile']['tmp_name'];
                $f_size = $_FILES['myfile']['size'];
                $f_extension = explode('.', $f_name);
                $f_extension = strtolower(end($f_extension));
                $f_newfile = uniqid().'.'.$f_extension;
                $store = "productimage/".$f_newfile;

                if(in_array($f_extension, ['jpg','jpeg','png','gif'])){
                    if($f_size >= 1000000){
                        $_SESSION['status']="Max file should be 1MB";
                        $_SESSION['status_code']="error";
                        $upload_success = false;
                    }else{
                        if(move_uploaded_file($f_tmp,$store)){
                            // Delete old image if new one is uploaded successfully
                            if($current_image && file_exists("productimage/".$current_image)){
                                @unlink("productimage/".$current_image);
                            }
                            $image_to_save = $f_newfile;
                        }else{
                            $_SESSION['status']="Failed to upload image";
                            $_SESSION['status_code']="error";
                            $upload_success = false;
                        }
                    }
                }else{
                    $_SESSION['status']="Only jpg, jpeg, png, gif allowed";
                    $_SESSION['status_code']="error";
                    $upload_success = false;
                }
            }

            // Only update if no errors occurred
            if($upload_success){
                // Check if product_unit column exists
                $checkColumn = $pdo->query("SHOW COLUMNS FROM tbl_product LIKE 'product_unit'");
                $hasProductUnit = $checkColumn->rowCount() > 0;
                
                if($hasProductUnit){
                    $update = $pdo->prepare("UPDATE tbl_product SET 
                        product=:product,
                        product_unit=:unit,
                        category=:category,
                        description=:description,
                        stock=:stock,
                        purchaseprice=:pprice,
                        saleprice=:sprice,
                        image=:image
                        WHERE pid=:pid");
                    $update->bindParam(':unit', $product_unit_txt, PDO::PARAM_STR);
                } else {
                    $update = $pdo->prepare("UPDATE tbl_product SET 
                        product=:product,
                        category=:category,
                        description=:description,
                        stock=:stock,
                        purchaseprice=:pprice,
                        saleprice=:sprice,
                        image=:image
                        WHERE pid=:pid");
                }

                $update->bindParam(':product', $product_txt, PDO::PARAM_STR);
                $update->bindParam(':category', $category_txt, PDO::PARAM_STR);
                $update->bindParam(':description', $description_txt, PDO::PARAM_STR);
                $update->bindParam(':stock', $stock_txt, PDO::PARAM_INT);
                $update->bindParam(':pprice', $purchaseprice_txt);
                $update->bindParam(':sprice', $saleprice_txt);
                $update->bindParam(':image', $image_to_save, PDO::PARAM_STR);
                $update->bindParam(':pid', $id, PDO::PARAM_INT);

                if($update->execute()){
                    $_SESSION['status']="Product Updated Successfully";
                    $_SESSION['status_code']="success";
                    // Clear output buffer and redirect
                    ob_end_clean();
                    header("Location: productlist.php");
                    exit();
                }else{
                    $_SESSION['status']="Product Update Failed";
                    $_SESSION['status_code']="error";
                }
            }
        } catch(Exception $e) {
            $_SESSION['status']="Error: " . $e->getMessage();
            $_SESSION['status_code']="error";
        }
        
        // Clear output buffer if still active
        if(ob_get_level() > 0) ob_end_clean();
    }
    ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-box-open"></i> Product Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Products</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-outline card-primary shadow">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="card-title"><i class="fas fa-list"></i> Product List</h3>
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#addProductModal">
                                        <i class="fas fa-plus-circle"></i> Add New Product
                                    </button>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered" id="table_product">
                                        <thead class="bg-primary">
                                            <tr>
                                                <th class="text-center"><i class="fas fa-barcode"></i> Barcode</th>
                                                <th><i class="fas fa-box"></i> Product</th>
                                                <th class="text-center"><i class="fas fa-ruler"></i> Unit</th>
                                                <th><i class="fas fa-tags"></i> Category</th>
                                                <th><i class="fas fa-info-circle"></i> Description</th>
                                                <th class="text-center"><i class="fas fa-cubes"></i> Stock</th>
                                                <th class="text-center"><i class="fas fa-dollar-sign"></i> Purchase</th>
                                                <th class="text-center"><i class="fas fa-money-bill-wave"></i> Sale Price</th>
                                                <th class="text-center"><i class="fas fa-image"></i> Image</th>
                                                <th class="text-center"><i class="fas fa-cog"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $select = $pdo->prepare("SELECT * FROM tbl_product ORDER BY pid ASC");
                                        $select->execute();

                        while ($row = $select->fetch(PDO::FETCH_OBJ)) {
                            $product_unit = isset($row->product_unit) ? $row->product_unit : 'pcs';
                            echo '<tr>
                                <td class="text-center"><strong>'.$row->barcode.'</strong></td>
                                <td><strong>'.$row->product.'</strong></td>
                                <td class="text-center"><span class="badge badge-info badge-pill">'.$product_unit.'</span></td>
                                <td><span class="badge badge-secondary">'.$row->category.'</span></td>
                                <td><small>'.$row->description.'</small></td>';

                            if($row->stock > 10){
                                echo '<td class="text-center"><span class="badge badge-success badge-pill px-3 py-2"><i class="fas fa-check-circle"></i> '.$row->stock.' '.$product_unit.'</span></td>';
                            } elseif($row->stock > 0 && $row->stock <= 10){
                                echo '<td class="text-center"><span class="badge badge-warning badge-pill px-3 py-2"><i class="fas fa-exclamation-triangle"></i> '.$row->stock.' '.$product_unit.'</span></td>';
                            } else {
                                echo '<td class="text-center"><span class="badge badge-danger badge-pill px-3 py-2"><i class="fas fa-times-circle"></i> Out of Stock</span></td>';
                            }

                            echo '<td class="text-center"><strong>₱'.number_format($row->purchaseprice, 2).'</strong></td>
                                <td class="text-center"><strong class="text-success">₱'.number_format($row->saleprice, 2).'</strong></td>
                                <td class="text-center">
                                    <img src="productimage/'.$row->image.'" class="img-thumbnail" width="50" height="50" style="object-fit: cover; cursor: pointer;" data-toggle="modal" data-target="#imageModal'.$row->pid.'" alt="Product Image">
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="printbarcode.php?id='.$row->pid.'" class="btn btn-sm btn-dark" title="Print Barcode">
                                            <i class="fas fa-barcode"></i>
                                        </a>
                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#editProductModal'.$row->pid.'" title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button id="'.$row->pid.'" class="btn btn-sm btn-danger btndelete" title="Delete Product">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>';

                            // =============== IMAGE PREVIEW MODAL =================
                            ?>
                            <div class="modal fade" id="imageModal<?php echo $row->pid;?>" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-dark">
                                            <h5 class="modal-title"><?php echo $row->product;?></h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <img src="productimage/<?php echo $row->image;?>" class="img-fluid" alt="Product Image">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php
                            // =============== EDIT PRODUCT MODAL =================
                            ?>
                            <div class="modal fade" id="editProductModal<?php echo $row->pid;?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <form action="" method="post" enctype="multipart/form-data">
                                        <div class="modal-header bg-info">
                                            <h5 class="modal-title text-white"><i class="fas fa-edit"></i> Edit Product</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="edit_pid" value="<?php echo $row->pid;?>">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label><i class="fas fa-box"></i> Product Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="txtproductname" value="<?php echo htmlspecialchars($row->product);?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label><i class="fas fa-tags"></i> Category <span class="text-danger">*</span></label>
                                                        <select class="form-control" name="txtselect_option" required>
                                                            <option disabled>Select Category</option>
                                                            <?php
                                                            $cat_select=$pdo->prepare("SELECT * FROM tbl_category ORDER BY catid DESC");
                                                            $cat_select->execute();
                                                            while($cat_row=$cat_select->fetch(PDO::FETCH_ASSOC)){
                                                                $selected = ($cat_row['category']==$row->category)?'selected':'';
                                                                echo '<option '.$selected.'>'.htmlspecialchars($cat_row['category']).'</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label><i class="fas fa-info-circle"></i> Description</label>
                                                        <textarea class="form-control" name="txtdescription" rows="3" placeholder="Product description..."><?php echo htmlspecialchars($row->description);?></textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <label><i class="fas fa-image"></i> Product Image</label><br>
                                                        <img src="productimage/<?php echo $row->image;?>" class="img-thumbnail mb-2" width="80" height="80" style="object-fit: cover;">
                                                        <input type="file" class="form-control-file" name="myfile" accept="image/*">
                                                        <small class="form-text text-muted">Max file size: 1MB (jpg, jpeg, png, gif)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label><i class="fas fa-cubes"></i> Stock Quantity <span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" name="txtstock" value="<?php echo intval($row->stock);?>" step="" min="" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label><i class="fas fa-dollar-sign"></i> Purchase Price <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">₱</span>
                                                            </div>
                                                            <input type="number" class="form-control" name="txtpurchaseprice" value="<?php echo $row->purchaseprice;?>" step="0.01" min="0" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label><i class="fas fa-money-bill-wave"></i> Sale Price <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">₱</span>
                                                            </div>
                                                            <input type="number" class="form-control" name="txtsaleprice" value="<?php echo $row->saleprice;?>" step="0.01" min="0" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label><i class="fas fa-ruler"></i> Product Unit <span class="text-danger">*</span></label>
                                                        <select class="form-control" name="txtproduct_unit" required>
                                                            <?php
                                                            $units = ['pcs' => 'Pieces (PCS)', 'set' => 'Set (SET)', 'box' => 'Box (BOX)', 'pack' => 'Pack (PACK)', 'kg' => 'Kilogram (KG)'];
                                                            $current_unit = isset($row->product_unit) && !empty($row->product_unit) ? trim($row->product_unit) : 'pcs';
                                                            foreach($units as $value => $label) {
                                                                $selected = ($current_unit == $value) ? 'selected' : '';
                                                                echo '<option value="'.htmlspecialchars($value).'" '.$selected.'>'.htmlspecialchars($label).'</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                                            <button type="submit" class="btn btn-info" name="btneditproduct"><i class="fas fa-save"></i> Update Product</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            </div>
                                        <?php
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
    </div>

    <!-- ================= ADD PRODUCT MODAL ================= -->
    <div class="modal fade" id="addProductModal">
    <div class="modal-dialog modal-lg">
    <div class="modal-content">
        <form action="" method="post" enctype="multipart/form-data">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-plus-circle"></i> Add New Product</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-barcode"></i> Barcode (Optional)</label>
                            <input type="text" class="form-control" name="txtbarcode" placeholder="Leave blank for auto-generate">
                            <small class="form-text text-muted">Leave blank to auto-generate barcode</small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-box"></i> Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="txtproductname" placeholder="Enter product name" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-tags"></i> Category <span class="text-danger">*</span></label>
                            <select class="form-control" name="txtselect_option" required>
                                <option disabled selected>Select Category</option>
                                <?php
                                $select=$pdo->prepare("SELECT * FROM tbl_category ORDER BY catid DESC");
                                $select->execute();
                                while($row=$select->fetch(PDO::FETCH_ASSOC)){
                                    echo '<option>'.htmlspecialchars($row['category']).'</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-info-circle"></i> Description</label>
                            <textarea class="form-control" name="txtdescription" rows="3" placeholder="Product description..."></textarea>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-cubes"></i> Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="txtstock" min="" step="1" placeholder="" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-dollar-sign"></i> Purchase Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₱</span>
                                </div>
                                <input type="number" class="form-control" name="txtpurchaseprice" min="0" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Sale Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₱</span>
                                </div>
                                <input type="number" class="form-control" name="txtsaleprice" min="0" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-ruler"></i> Product Unit <span class="text-danger">*</span></label>
                            <select class="form-control" name="txtproduct_unit" required>
                                <option value="" disabled selected>Select Unit</option>
                                <option value="pcs">Pieces (PCS)</option>
                                <option value="set">Set (SET)</option>
                                <option value="box">Box (BOX)</option>
                                <option value="pack">Pack (PACK)</option>
                                <option value="kg">Kilogram (KG)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-image"></i> Product Image <span class="text-danger">*</span></label>
                            <input type="file" class="form-control-file" name="myfile" accept="image/*" required>
                            <small class="form-text text-muted">Max file size: 1MB (jpg, jpeg, png, gif)</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="btn btn-primary" name="btnsave"><i class="fas fa-save"></i> Save Product</button>
            </div>
        </form>
    </div>
    </div>
    </div>

    <?php include_once "footer.php"; ?>

    <style>
    /* Custom Styling for Product List */
    .content-wrapper {
        background-color: #f4f6f9;
    }
    
    .card {
        border-radius: 10px;
        border: none;
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
        padding: 15px 20px;
    }
    
    .table thead {
        border-radius: 8px;
    }
    
    .table thead th {
        border: none;
        color: white;
        font-weight: 600;
        vertical-align: middle;
        padding: 12px 8px;
        font-size: 0.9rem;
    }
    
    .table tbody td {
        vertical-align: middle;
        padding: 12px 8px;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    
    .badge-pill {
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .btn-group .btn {
        margin: 0 2px;
    }
    
    .img-thumbnail {
        border-radius: 8px;
        transition: transform 0.2s;
    }
    
    .img-thumbnail:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .modal-content {
        border-radius: 10px;
        border: none;
    }
    
    .modal-header {
        border-radius: 10px 10px 0 0;
        border-bottom: none;
    }
    
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    
    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .shadow {
        box-shadow: 0 0 1rem rgba(0,0,0,.15) !important;
    }
    
    /* DataTable custom styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #007bff !important;
        border-color: #007bff !important;
        color: white !important;
    }
    
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 5px;
        border: 1px solid #ced4da;
        padding: 5px 10px;
    }
    
    /* Responsive text */
    @media (max-width: 768px) {
        .table thead th {
            font-size: 0.75rem;
            padding: 8px 4px;
        }
        
        .table tbody td {
            font-size: 0.85rem;
            padding: 8px 4px;
        }
        
        .btn-sm {
            font-size: 0.75rem;
        }
    }
    </style>

    <script>
    $(document).ready(function () {

        // Initialize DataTable with custom settings
        $('#table_product').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "order": [[1, "asc"]],
            "language": {
                "search": "Search Products:",
                "lengthMenu": "Show _MENU_ products per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ products",
                "infoEmpty": "No products available",
                "infoFiltered": "(filtered from _MAX_ total products)",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });

        // Delete product with confirmation
        $('.btndelete').click(function(){
            var tdt=$(this);
            var id=$(this).attr('id');

            Swal.fire({
                title: "Delete this product?",
                text: "You won't be able to revert this action!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel'
            }).then((result)=>{
                if(result.isConfirmed){
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: 'productdelete.php',
                        type: 'post',
                        data: {pidd: id},
                        success: function(response){
                            console.log('Response:', response);
                            if(response.trim() === 'success'){
                                tdt.parents('tr').fadeOut(400, function(){
                                    $(this).remove();
                                });
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Product has been deleted successfully.",
                                    icon: "success",
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else if(response.indexOf('Cannot delete') !== -1) {
                                Swal.fire({
                                    title: "Cannot Delete!",
                                    text: response,
                                    icon: "warning",
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: response || "Failed to delete product.",
                                    icon: "error",
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error){
                            console.error('AJAX Error:', error);
                            Swal.fire({
                                title: "Error!",
                                text: "Connection error. Please try again.",
                                icon: "error",
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        });

        // Show session status messages
        <?php if(isset($_SESSION['status']) && $_SESSION['status']!=''){ ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['status_code'];?>',
                title: '<?php echo $_SESSION['status'];?>',
                showConfirmButton: true,
                timer: 3000
            });
        <?php unset($_SESSION['status']); } ?>

    });
    </script>