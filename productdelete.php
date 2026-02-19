<?php
include_once 'connectdb.php';

try {
    if(isset($_POST['pidd'])) {
        $id = intval($_POST['pidd']);
        
        // Check if product is used in any invoice
        $checkInvoice = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_invoice_details WHERE product_id = :pid");
        $checkInvoice->bindParam(':pid', $id, PDO::PARAM_INT);
        $checkInvoice->execute();
        $invoiceCount = $checkInvoice->fetch(PDO::FETCH_ASSOC);
        
        if($invoiceCount['count'] > 0) {
            echo "Cannot delete product. It is used in " . $invoiceCount['count'] . " invoice(s).";
        } else {
            // Get product image first to delete it
            $selectImg = $pdo->prepare("SELECT image FROM tbl_product WHERE pid = :pid");
            $selectImg->bindParam(':pid', $id, PDO::PARAM_INT);
            $selectImg->execute();
            $product = $selectImg->fetch(PDO::FETCH_ASSOC);
            
            // Delete the product
            $delete = $pdo->prepare("DELETE FROM tbl_product WHERE pid = :pid");
            $delete->bindParam(':pid', $id, PDO::PARAM_INT);
            
            if($delete->execute()) {
                // Delete image file if exists
                if($product && !empty($product['image']) && file_exists("productimage/".$product['image'])){
                    @unlink("productimage/".$product['image']);
                }
                echo "success";
            } else {
                echo "Error in deleting product";
            }
        }
    } else {
        echo "Invalid request";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>