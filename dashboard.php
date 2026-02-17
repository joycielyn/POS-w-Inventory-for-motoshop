<?php

include_once'connectdb.php';
session_start();

if($_SESSION['useremail']==""){
header('location:../index.php');
}

include_once"header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AdminLTE 3 | Dashboard</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>

<div class="content-wrapper">

<div class="content-header">
<div class="container-fluid">
<div class="row mb-2">
<div class="col-sm-6">
<h1 class="m-0">Dashboard</h1>
<hr>
</div>
</div>
</div>
</div>

<section class="content">
<div class="container-fluid">

<div class="row">

<!-- SALES -->
<div class="col-lg-3 col-6">
<div class="small-box bg-info">
<div class="inner">
<h3>Sales</h3>
<p>Sales</p>
</div>
<div class="icon">
<i class="ion ion-stats-bars"></i>
</div>

<a href="#" class="small-box-footer"
data-toggle="modal"
data-target="#filterModal"
data-type="Sales">
More info <i class="fas fa-arrow-circle-right"></i>
</a>

</div>
</div>

<!-- PRODUCTS -->
<div class="col-lg-3 col-6">
<div class="small-box bg-success">
<div class="inner">
<h3>Products</h3>
<p>Products</p>
</div>
<div class="icon">
<i class="fas fa-box"></i>
</div>

<a href="#" class="small-box-footer"
data-toggle="modal"
data-target="#filterModal"
data-type="Products">
More info <i class="fas fa-arrow-circle-right"></i>
</a>

</div>
</div>

<!-- USERS -->
<div class="col-lg-3 col-6">
<div class="small-box bg-warning">
<div class="inner">
<h3>Users</h3>
<p>Users</p>
</div>
<div class="icon">
<i class="ion ion-person-add"></i>
</div>

<a href="#" class="small-box-footer"
data-toggle="modal"
data-target="#filterModal"
data-type="Users">
More info <i class="fas fa-arrow-circle-right"></i>
</a>

</div>
</div>

<!-- REVENUE -->
<div class="col-lg-3 col-6">
<div class="small-box bg-danger">
<div class="inner">
<h3>Revenue</h3>
<p>Revenue</p>
</div>
<div class="icon">
<i class="fas fa-wallet"></i>
</div>

<a href="#" class="small-box-footer"
data-toggle="modal"
data-target="#filterModal"
data-type="Revenue">
More info <i class="fas fa-arrow-circle-right"></i>
</a>

</div>
</div>

</div>
</div>
</section>

</div>

<!-- ================= MODAL ================= -->
<div class="modal fade" id="filterModal">
<div class="modal-dialog">
<div class="modal-content">

<form method="POST" action="filter.php">

<div class="modal-header">
<h5 class="modal-title" id="modalTitle">Filter</h5>
<button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">

<input type="hidden" name="type" id="modalType">

<div class="form-group">
<label>Select Date</label>
<input type="date" name="date" class="form-control">
</div>

<div class="form-group">
<label>Select Week</label>
<input type="week" name="week" class="form-control">
</div>

<div class="form-group">
<label>Select Month</label>
<input type="month" name="month" class="form-control">
</div>

<div class="form-group">
<label>Select Year</label>
<input type="number" name="year" min="2000" max="2100"
class="form-control" placeholder="Enter year">
</div>

</div>

<div class="modal-footer">
<button type="submit" class="btn btn-primary">Apply Filter</button>
</div>

</form>

</div>
</div>
</div>
<!-- ================= END MODAL ================= -->

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.js"></script>

<script>
$('#filterModal').on('show.bs.modal', function (event) {

var button = $(event.relatedTarget);
var type = button.data('type');

$('#modalTitle').text('Filter ' + type);
$('#modalType').val(type);

});
</script>

</body>

<?php include_once"footer.php"; ?>
