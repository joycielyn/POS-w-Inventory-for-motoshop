<?php
include_once "ui/connectdb.php";
session_start();

if (isset($_POST['btn_login'])) {

    $userInput = $_POST['txt_email'];
    $password  = $_POST['txt_password'];

    if (strpos($userInput, '@') !== false) {
        $select = $pdo->prepare("SELECT * FROM tbl_user WHERE useremail = :user AND userpassword = :password");
    } else {
        $select = $pdo->prepare("SELECT * FROM tbl_user WHERE username = :user AND userpassword = :password");
    }

    $select->bindParam(':user', $userInput);
    $select->bindParam(':password', $password);
    $select->execute();

    $row = $select->fetch(PDO::FETCH_ASSOC);

   var_dump($row['role']);
        $_SESSION['userid']   = $row['userid'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['useremail']= $row['useremail'];
        $_SESSION['role']     = $row['role'];

       if (strtolower(trim($row['role'])) === "admin") {
              header("Location: ui/dashboard.php");
              exit;
          } else {
              header("Location: ui/user.php");
              exit;
          }


    } else {
        $error = "Invalid username/email or password";
    }

?>

<!DOCTYPE html>
  <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Login</title>

      <script src="https://cdn.tailwindcss.com"></script>
      <script src="https://unpkg.com/feather-icons"></script>

      <style>
      @keyframes fadeIn {
          from { opacity: 0; transform: translateY(10px); }
          to   { opacity: 1; transform: translateY(0); }
      }
      .login-container {
          animation: fadeIn .5s ease-out forwards;
      }
      </style>
    </head>

        <body class="min-h-screen flex items-center justify-center bg-no-repeat"style="background-image:linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)),url('assets/logo.jpg');background-size: auto 100%;background-position: center;">

          <div class="login-container w-full max-w-md">
            <div class="text-center mb-8">
                          <h1 class="text-2xl font-bold text-white-600">Motorshop </h1>
                          <p class="mt-2 text-indigo-600">Inventory and Point of Sale System</p>
                      </div>

          <div class="bg-white/90 backdrop-blur-md rounded-xl shadow-2xl overflow-hidden">

          <!-- HEADER -->
          <div class="bg-red-400 py-6 px-8 text-center">
              <h1 class="text-2xl font-bold text-white">Welcome</h1>
              <p class="text-red-100 mt-1">Sign in to your account</p>
          </div>

         

          <!-- FORM -->
          <form method="POST" action="" class="px-8 py-6">

          <div class="mb-4">
          <label class="block text-gray-700 text-sm font-medium mb-2">
              Email or Username
          </label>
          <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i data-feather="user" class="text-gray-400"></i>
              </div>
              <input type="text"
                    name="txt_email"
                    required
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400"
                    placeholder="email or username">
          </div>
          </div>

          <div class="mb-6">
          <label class="block text-gray-700 text-sm font-medium mb-2">
              Password
          </label>
          <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i data-feather="lock" class="text-gray-400"></i>
              </div>
              <input type="password"
                    name="txt_password"
                    required
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400"
                    placeholder="••••••••">
          </div>
          </div>

          <button type="submit"
                  name="btn_login"
                  class="w-full bg-red-400 hover:bg-red-500 text-white font-medium py-2 rounded-lg transition">
              Sign In
          </button>

    </form>
      </div>
    </div>

          <script>
          feather.replace();
          </script>

        </body>
</html>
