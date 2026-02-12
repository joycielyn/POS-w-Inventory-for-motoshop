<?php

include_once 'connectdb.php';
session_start();
include_once "header.php";

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Order List</h1>
                    <hr>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h5 class="m-0">Product List :</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped table-hover" id="table_product">
                                <thead>
                                    <tr>
                                        <td>Invoice ID</td>
                                        <td>Order Date</td>
                                        <td>Total</td>
                                        <td>Paid</td>
                                        <td>Due</td>
                                        <td>Payment Type</td>
                                        <td>Action</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $select = $pdo->prepare("SELECT * FROM tbl_invoice ORDER BY invoice_id ASC");
                                    $select->execute();

                                    while ($row = $select->fetch(PDO::FETCH_OBJ)) {
                                        echo '
                                        <tr>
                                            <td>' . $row->invoice_id . '</td>
                                            <td>' . $row->order_date . '</td>
                                            <td>' . $row->total . '</td>
                                            <td>' . $row->paid . '</td>
                                            <td>' . $row->due . '</td>';

                                        if ($row->payment_type == "Cash") {
                                            echo '<td><span class="badge badge-warning">' . $row->payment_type . '</span></td>';
                                        } else if ($row->payment_type == "Card") {
                                            echo '<td><span class="badge badge-success">' . $row->payment_type . '</span></td>';
                                        } else {
                                            echo '<td><span class="badge badge-danger">' . $row->payment_type . '</span></td>';
                                        }

                                        echo '
                                        <td>
                                            <div class="btn-group">
                                                <a href="printbill.php?id=' . $row->invoice_id . '" class="btn btn-primary" role="button">
                                                    <span class="fa fa-print" style="color:#ffffff" data-toggle="tooltip" title="Print Bill"></span>
                                                </a>
                                                <a href="editorderpos.php?id=' . $row->invoice_id . '" class="btn btn-success" role="button">
                                                    <span class="fa fa-edit" style="color:#ffffff" data-toggle="tooltip" title="Edit Order"></span>
                                                </a>
                                                <button id="' . $row->invoice_id . '" class="btn btn-danger btndelete">
                                                    <span class="fa fa-trash" style="color:#ffffff" data-toggle="tooltip" title="Delete Order"></span>
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
            <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php include_once("footer.php"); ?>

<script>
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();

    $('.btndelete').click(function() {
        var tdh = $(this);
        var id = $(this).attr("id");

        Swal.fire({
            title: "Do you want to delete?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'orderdelete.php',
                    type: 'post',
                    data: {
                        pidd: id
                    },
                    success: function(data) {
                        if (data == "Success") {
                            tdh.parents('tr').hide();
                            Swal.fire({
                                title: "Deleted!",
                                text: "Product has been deleted.",
                                icon: "success"
                            });
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: "There was an error deleting the product.",
                                icon: "error"
                            });
                        }
                    }
                });
            }
        });
    });
});

$(document).ready(function() {
    $('#table_product').DataTable({
        "order": [[0, "desc"]]
    });
});
</script>