<?php


include_once 'connectdb.php';
session_start();


if($_SESSION['useremail']=="" OR $_SESSION['role']=="Admin"){

header('location:../index.php');

}



include_once"headeruser.php";



?>



  <?php

include_once"footer.php";


?>
