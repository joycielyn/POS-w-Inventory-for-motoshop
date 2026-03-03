<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>POS W INVENTORY SYSTEM </title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini sidebar-collapse">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-black navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar bg-dark elevation-4">
    <!-- Brand Logo -->
    <a href="profile.php" class="brand-link">
      <img src="../dist/img/logo.jpg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">MOTORSHOP</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../dist/img/logo.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <!-- Make the username text white -->
          <a href="profile.php" class="d-block" style="color: #fff;"><?php echo $_SESSION['username']; ?></a>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>
                    <li class="nav-item">
            <a href="supplier.php" class="nav-link">
              <i class="nav-icon fas fa-table"></i>
              <p>
                Supplier
                
              </p>
            </a>
          </li> 

          <li class="nav-item">
            <a href="productlist.php" class="nav-link"> 
              <i class="nav-icon fas fa-edit"></i>
              <p>Product List</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="orderlist.php" class="nav-link">
              <i class="nav-icon fas fa-list"></i>
              <p>Order List</p>
            </a>
          </li>
                    <li class="nav-item">
            <a href="pos.php" class="nav-link">
              <i class="nav-icon fas fa-book"></i>
              <p>
                POS
               
              </p>
            </a>
          </li>
                    <li class="nav-item">
            <a href="taxdis.php" class="nav-link">
              <i class="nav-icon fas fa-calculator"></i>
              <p>
                Tax
               
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="salesreport.php" class="nav-link">
              <i class="nav-icon fas fa-chart-pie"></i>
              <p>Sales Report</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="registration.php" class="nav-link">
              <i class="nav-icon far fa-plus-square"></i>
              <p>
                Registration
               
              </p>
            </a>
          </li>

         <!-- Utilities Dropdown Start -->
<li class="nav-item has-treeview">
  <a href="#" class="nav-link">
    <i class="nav-icon fas fa-tools"></i>
    <p>
      Utilities
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>
  <ul class="nav nav-treeview">
    <li class="nav-item">
      <a href="changepassword.php" class="nav-link">
        <i class="nav-icon fas fa-user-lock"></i>
        <p>Change Password</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="archive.php" class="nav-link">
        <i class="nav-icon fas fa-archive"></i>
        <p>Archived Products</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="trashbin.php" class="nav-link">
        <i class="nav-icon fas fa-trash"></i>
        <p>Trash Bin</p>
      </a>
    </li>
  </ul>
</li>

          <li class="nav-item">
            <a href="logout.php" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>Logout</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>

  <style>

    .main-header.navbar-black {
      background-color: #000 !important; /* Set the navbar background color to black */
    }

    /* Custom Sidebar Color */
    .main-sidebar {
      background-color: #000 !important; /* Set the sidebar color to black */
    }

    .sidebar-dark-primary {
      background-color: #000 !important; /* Ensure dark sidebar is black */
    }

    /* Make the brand text (Hubek Cafe) white */
    .sidebar .brand-text {
      color: #fff !important; /* White color for brand text */
    }

    /* Make the sidebar search input text white */
    .sidebar .form-control-sidebar {
      background-color: #333 !important;  /* Dark background for search box */
      color: #fff !important;              /* White text for the input */
      border: 1px solid #444 !important;  /* Optional border styling */
    }

    .sidebar .form-control-sidebar:focus {
      background-color: #444 !important;  /* Darker background on focus */
      color: #fff !important;             /* White text when focused */
      box-shadow: none;                   /* Remove focus shadow */
    }

    /* Make the search button white */
    .sidebar .btn-sidebar {
      background-color: #444 !important;  /* Dark background for search button */
      color: #fff !important;             /* White icon color */
    }

    /* Prevent blue color on focus/hover for sidebar links */
    .sidebar .nav-link {
      color: #fff !important; /* Ensure all sidebar links are white */
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link:focus {
      color: #fff !important; /* Keep text white even on hover/focus */
      background-color: #444 !important; /* Optional: Change background color on hover */
    }


    
  </style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
