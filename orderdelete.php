<?php
include_once 'connectdb.php';

if (isset($_POST['pidd'])) {
    $id = $_POST['pidd'];

    // Start a transaction
    $pdo->beginTransaction();

    try {
        // Retrieve details of the products from tbl_invoice_details
        $selectDetails = $pdo->prepare("SELECT product_id, qty FROM tbl_invoice_details WHERE invoice_id = :id");
        $selectDetails->bindParam(':id', $id);
        $selectDetails->execute();
        $products = $selectDetails->fetchAll(PDO::FETCH_ASSOC);

        // Update the stock for each product
        foreach ($products as $product) {
            $updateStock = $pdo->prepare("UPDATE tbl_product SET stock = stock + :qty WHERE pid = :product_id");
            $updateStock->bindParam(':qty', $product['qty']);
            $updateStock->bindParam(':product_id', $product['product_id']);
            $updateStock->execute();
        }

        // Delete from tbl_invoice_details
        $deleteDetails = $pdo->prepare("DELETE FROM tbl_invoice_details WHERE invoice_id = :id");
        $deleteDetails->bindParam(':id', $id);
        $deleteDetails->execute();

        // Delete from tbl_invoice
        $deleteInvoice = $pdo->prepare("DELETE FROM tbl_invoice WHERE invoice_id = :id");
        $deleteInvoice->bindParam(':id', $id);
        $deleteInvoice->execute();

        // Commit the transaction
        $pdo->commit();
        echo "Success";
    } catch (PDOException $e) {
        // Rollback the transaction in case of an error
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>