<?php

include_once 'connectdb.php';
session_start();


if($_SESSION['useremail']=="" OR $_SESSION['role']=="User"){

  header('location:../index.php');
  
  }


   if($_SESSION['role']=="Admin"){

    include_once"header.php";


   }else{

include_once"header.php";

   }

error_reporting(0);

$id=$_GET['id'];

if(isset($id)){

$delete=$pdo->prepare("delete from tbl_user where userid =".$id);

if($delete->execute()){
  $_SESSION['status']="Account deleted successfully";
  $_SESSION['status_code']="success";

}else{

  $_SESSION['status']="Account is not deleted";
  $_SESSION['status_code']="warning";

 }
}

if(isset($_POST['btnsave'])){
  $username = $_POST['txtname'];
  $useremail = $_POST['txtemail'];
  $userpassword = $_POST['txtpassword'];
  $useraddress = $_POST['txtaddress'];
  $userage = $_POST['txtage'];
  $usercontact = $_POST['txtcontact'];
  $role = $_POST['txtselect_option'];

  if(($_POST['txtage'])<18){

    $_SESSION['status']="Minor are not allowed";
    $_SESSION['status_code']="warning";
  }

elseif(isset($_POST['txtemail'])){

$select=$pdo->prepare("select useremail from tbl_user where useremail='$useremail'");

$select->execute();


if($select->rowCount()>0){
  

  $_SESSION['status']="Email already exists. Create Account From New Email";
  $_SESSION['status_code']="warning";

}else{
 
  if(isset($_POST['txtpassword'])){


    $select=$pdo->prepare("select userpassword from tbl_user where userpassword='$userpassword'");
    
    $select->execute();
    
    
    if($select->rowCount()>0){
    
      $_SESSION['status']="password already exists. Create new password";
      $_SESSION['status_code']="warning";
    
    
    }else{
 
  $insert=$pdo->prepare("insert into tbl_user (username,useremail,userpassword,useraddress,userage,usercontact,role) values (:name,:email,:password,:address,:age,:contact,:role)");

  $insert->bindParam(':name',$username);
  $insert->bindParam(':email',$useremail);
  $insert->bindParam(':password',$userpassword);
  $insert->bindParam(':address',$useraddress);
  $insert->bindParam(':age',$userage);
  $insert->bindParam(':contact',$usercontact);
  $insert->bindParam(':role',$role);

  if($insert->execute()){
  
  
  $_SESSION['status']="Insert successfully the user into the database";
  $_SESSION['status_code']="success";
  
  }else{
   
  $_SESSION['status']="Error inserting the user into the database";
  $_SESSION['status_code']="error";
  
  }
  
}

}
  
  }
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
            <h1 class="m-0">Registration</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
             
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid --
    </div>
    <-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        
            

          <div class="card card-primary card-outline">
              <div class="card-header">
                <h5 class="m-0">Registration</h5>
              </div>
              <div class="card-body">

<div class="row">
<div class="col-md-4">

<form action="" method="post">
                
                  <div class="form-group">
                    <label for="exampleInputEmail1">Name</label>
                    <input type="text" class="form-control" placeholder="Enter name" name="txtname" required>
                  </div>

                  <div class="form-group">
                    <label for="exampleInputEmail1">Email Address</label>
                    <input type="email" class="form-control" placeholder="Enter email" name="txtemail" required>
                  </div>

                  <div class="form-group">
                    <label for="exampleInputPassword1">Password</label>
                    <input type="password" class="form-control"  placeholder="Password" name="txtpassword" required>
                  </div>

                  <div class="form-group">
                    <label for="exampleInputAddress">Address</label>
                    <input type="Address" class="form-control" placeholder="Address" name="txtaddress" required>
                  </div>

                  <div class="form-group">
                    <label for="exampleInputAge">Age</label>
                    <input type="number" class="form-control"  placeholder="Enter Age" name="txtage" required>
                  </div>

                  <div class="form-group">
                    <label for="exampleInputContact">Contact</label>
                    <input type="Contact" class="form-control" placeholder="Contact" name="txtcontact" required>
                  </div>

                  <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" name="txtselect_option" required>
                          <option value="" disabled selected>Select Role</option>
                          <option>Admin</option>
                          <option>User</option>
                          
                        </select>
                      </div>
               

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary" name="btnsave">Save</button>
                </div>
              </form>




</div>






<div class="col-md-8">

<table class="table table-striped table-hover">
<thead>
<tr> 
 <td>#</td>
 <td>Name</td>
 <td>Email</td>
 <td>Password</td>
 <td>Address</td>
 <td>Age</td>
 <td>Contact</td>
 <td>Role</td>
 <td>Edit</td>
 <td>Delete</td>
</tr>

</thead>


<?php

$select = $pdo->prepare("select * from tbl_user order by userid ASC");
$select->execute();

while($row=$select->fetch(PDO::FETCH_OBJ))
{

echo'
<tr>
<td>'.$row->userid.'</td>
<td>'.$row->username.'</td>
<td>'.$row->useremail.'</td>
<td>'.$row->userpassword.'</td>
<td>'.$row->useraddress.'</td>
<td>'.$row->userage.'</td>
<td>'.$row->usercontact.'</td>
<td>'.$row->role.'</td>
<td>

 <button type="submit" class="btn btn-primary btn-edit" value="'.$row->userid.'" name="btnedit">Edit</button>
 </td>
 <td>
<a href="registration.php?id='.$row->userid.'" class="btn btn-danger delete-btn" data-id="'.$row->userid.'"><i class="fa fa-trash-alt"></i></a>
</td>

</tr>';

}

?>

<tbody>

 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script> 
      $(document).ready(function() {
  $('.delete-btn').click(function(e) {
    e.preventDefault();

    var userId = $(this).data('id');

    Swal.fire({
      title: 'Are you sure you want to delete this?',
      text: 'Once deleted, you will not be able to recover this account!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d63032',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Delete now!'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'registration.php?id=' + userId;
      }
    });
  }); 
});

</script>



</tbody>

</table>


</div>



               
               
            </div>



            </div>
        </div>
          <!-- /.col-md-6 -->
          

        

           
          
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <?php

include_once"footer.php";

?>

<?php
 if(isset($_SESSION['status']) && $_SESSION['status']!='')

 {
   
?>
<script>
     Swal.fire({
        icon: '<?php echo $_SESSION['status_code'];?>',
        title: '<?php echo $_SESSION['status'];?>'
      });
</script>

<?php
unset($_SESSION['status']);
  }
?>