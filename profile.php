<?php
include_once 'connectdb.php';
session_start();

if(function_exists('date_default_timezone_set')){
  @date_default_timezone_set('Asia/Manila');
}

if(!isset($_SESSION['useremail']) || $_SESSION['useremail'] === ''){
  header('location:../index.php');
  exit();
}

// Admin-only profile page
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin'){
  header('location:userdashboard.php');
  exit();
}

$email = (string)$_SESSION['useremail'];

// ===== CHANGE PASSWORD (MODAL) =====
if(isset($_POST['btn_change_password'])){
  $old = isset($_POST['old_password']) ? (string)$_POST['old_password'] : '';
  $new = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
  $cnf = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

  if($old === '' || $new === '' || $cnf === ''){
    $_SESSION['status'] = 'Please fill in all password fields.';
    $_SESSION['status_code'] = 'warning';
    header('location:profile.php?modal=password');
    exit();
  }
  if($new !== $cnf){
    $_SESSION['status'] = 'New password does not match.';
    $_SESSION['status_code'] = 'error';
    header('location:profile.php?modal=password');
    exit();
  }
  if(strlen($new) < 4){
    $_SESSION['status'] = 'Password must be at least 4 characters.';
    $_SESSION['status_code'] = 'warning';
    header('location:profile.php?modal=password');
    exit();
  }

  try {
    $sel = $pdo->prepare('SELECT userid, userpassword FROM tbl_user WHERE useremail = :email AND role = \'Admin\' LIMIT 1');
    $sel->bindValue(':email', $email);
    $sel->execute();
    $row = $sel->fetch(PDO::FETCH_ASSOC);

    if(!$row){
      throw new Exception('Admin user not found.');
    }

    if((string)$row['userpassword'] !== $old){
      $_SESSION['status'] = 'Old password is incorrect.';
      $_SESSION['status_code'] = 'error';
      header('location:profile.php?modal=password');
      exit();
    }

    $upd = $pdo->prepare('UPDATE tbl_user SET userpassword = :p WHERE userid = :id AND role = \'Admin\'');
    $upd->bindValue(':p', $new);
    $upd->bindValue(':id', (int)$row['userid'], PDO::PARAM_INT);
    $upd->execute();

    $_SESSION['status'] = 'Password updated successfully.';
    $_SESSION['status_code'] = 'success';
    header('location:profile.php');
    exit();

  } catch(Exception $e){
    $_SESSION['status'] = 'Password update failed: ' . $e->getMessage();
    $_SESSION['status_code'] = 'error';
    header('location:profile.php?modal=password');
    exit();
  }
}

// ===== UPDATE ADMIN PROFILE (MODAL) =====
if(isset($_POST['btn_update_admin_profile'])){
  $newName    = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
  $newEmail   = isset($_POST['useremail']) ? trim((string)$_POST['useremail']) : '';
  $newAddress = isset($_POST['useraddress']) ? trim((string)$_POST['useraddress']) : '';
  $newAge     = isset($_POST['userage']) && $_POST['userage'] !== '' ? (int)$_POST['userage'] : null;
  $newContact = isset($_POST['usercontact']) ? trim((string)$_POST['usercontact']) : '';

  if($newName === '' || $newEmail === ''){
    $_SESSION['status'] = 'Name and Email are required.';
    $_SESSION['status_code'] = 'error';
    header('location:profile.php?modal=edit');
    exit();
  }
  if(!filter_var($newEmail, FILTER_VALIDATE_EMAIL)){
    $_SESSION['status'] = 'Invalid email format.';
    $_SESSION['status_code'] = 'error';
    header('location:profile.php?modal=edit');
    exit();
  }
  if($newAge !== null && ($newAge < 0 || $newAge > 120)){
    $_SESSION['status'] = 'Invalid age.';
    $_SESSION['status_code'] = 'error';
    header('location:profile.php?modal=edit');
    exit();
  }

  try {
    $sel = $pdo->prepare('SELECT userid, userimage FROM tbl_user WHERE useremail = :email AND role = \'Admin\' LIMIT 1');
    $sel->bindValue(':email', $email);
    $sel->execute();
    $current = $sel->fetch(PDO::FETCH_ASSOC);

    if(!$current){
      $_SESSION['status'] = 'Admin user not found.';
      $_SESSION['status_code'] = 'error';
      header('location:dashboard.php');
      exit();
    }

    $userid = (int)$current['userid'];
    $currentImage = (string)$current['userimage'];

    // unique email check if changed
    if(strtolower($newEmail) !== strtolower($email)){
      $chk = $pdo->prepare('SELECT COUNT(*) FROM tbl_user WHERE useremail = :email AND userid <> :id');
      $chk->bindValue(':email', $newEmail);
      $chk->bindValue(':id', $userid, PDO::PARAM_INT);
      $chk->execute();
      if((int)$chk->fetchColumn() > 0){
        $_SESSION['status'] = 'This email is already registered.';
        $_SESSION['status_code'] = 'error';
        header('location:profile.php?modal=edit');
        exit();
      }
    }

    $newImageName = null;

    // Upload photo (optional)
    if(isset($_FILES['userimage']) && isset($_FILES['userimage']['error']) && $_FILES['userimage']['error'] === 0){
      $tmp  = $_FILES['userimage']['tmp_name'];
      $name = $_FILES['userimage']['name'];
      $size = (int)$_FILES['userimage']['size'];

      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','gif'];

      if(!in_array($ext, $allowed, true)){
        $_SESSION['status'] = 'Only jpg, jpeg, png, gif can be uploaded.';
        $_SESSION['status_code'] = 'error';
        header('location:profile.php?modal=edit');
        exit();
      }
      if($size > 1000000){
        $_SESSION['status'] = 'Max image size is 1MB.';
        $_SESSION['status_code'] = 'error';
        header('location:profile.php?modal=edit');
        exit();
      }

      if(!is_dir(__DIR__ . '/uploads')){
        @mkdir(__DIR__ . '/uploads', 0777, true);
      }

      $newImageName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
      $dest = __DIR__ . '/uploads/' . $newImageName;

      if(!move_uploaded_file($tmp, $dest)){
        $_SESSION['status'] = 'Failed to upload image.';
        $_SESSION['status_code'] = 'error';
        header('location:profile.php?modal=edit');
        exit();
      }

      // Remove old image (except default)
      $oldSafe = basename($currentImage);
      if($oldSafe !== '' && $oldSafe !== 'default.png'){
        $oldPath = __DIR__ . '/uploads/' . $oldSafe;
        if(file_exists($oldPath)){
          @unlink($oldPath);
        }
      }
    }

    if($newImageName !== null){
      $upd = $pdo->prepare('UPDATE tbl_user SET username=:name, useremail=:email, useraddress=:addr, userage=:age, usercontact=:contact, userimage=:img WHERE userid=:id AND role=\'Admin\'');
      $upd->bindValue(':img', $newImageName);
    } else {
      $upd = $pdo->prepare('UPDATE tbl_user SET username=:name, useremail=:email, useraddress=:addr, userage=:age, usercontact=:contact WHERE userid=:id AND role=\'Admin\'');
    }

    $upd->bindValue(':name', $newName);
    $upd->bindValue(':email', $newEmail);
    $upd->bindValue(':addr', $newAddress);
    if($newAge === null){
      $upd->bindValue(':age', null, PDO::PARAM_NULL);
    } else {
      $upd->bindValue(':age', $newAge, PDO::PARAM_INT);
    }
    $upd->bindValue(':contact', $newContact);
    $upd->bindValue(':id', $userid, PDO::PARAM_INT);
    $upd->execute();

    // Update session
    $_SESSION['username'] = $newName;
    $_SESSION['useremail'] = $newEmail;
    if($newImageName !== null){
      $_SESSION['userimage'] = $newImageName;
    }

    $_SESSION['status'] = 'Admin profile updated successfully.';
    $_SESSION['status_code'] = 'success';
    header('location:profile.php');
    exit();

  } catch(Exception $e){
    $_SESSION['status'] = 'Update failed: ' . $e->getMessage();
    $_SESSION['status_code'] = 'error';
    header('location:profile.php?modal=edit');
    exit();
  }
}

// ===== FETCH ADMIN INFO (include login/logout timestamps) =====
$admin = null;
try {
  $sel = $pdo->prepare('SELECT userid, username, useremail, useraddress, userage, usercontact, role, userimage, last_login_at, last_logout_at FROM tbl_user WHERE useremail = :email AND role = \'Admin\' LIMIT 1');
  $sel->bindValue(':email', $email);
  $sel->execute();
  $admin = $sel->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e){
  $admin = null;
}

include_once 'header.php';

$img = ($admin && !empty($admin['userimage'])) ? basename($admin['userimage']) : '';
$imgPath = 'uploads/default.png';
if($img !== '' && file_exists(__DIR__ . '/uploads/' . $img)){
  $imgPath = 'uploads/' . rawurlencode($img);
}

$nowLabel = date('M d, Y h:i A');
$lastLogin = ($admin && !empty($admin['last_login_at'])) ? date('M d, Y h:i A', strtotime((string)$admin['last_login_at'])) : '—';
$lastLogout = ($admin && !empty($admin['last_logout_at'])) ? date('M d, Y h:i A', strtotime((string)$admin['last_logout_at'])) : '—';

$autoModal = isset($_GET['modal']) ? strtolower(trim((string)$_GET['modal'])) : '';
?>

<div class="content-wrapper" style="background:#f4f6f9;">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between flex-wrap">
        <div class="mb-2">
          <h1 class="m-0">Admin Profile</h1>
          <small class="text-muted">Server time: <?php echo htmlspecialchars($nowLabel); ?></small>
        </div>
        <div class="mb-2">
          <ol class="breadcrumb float-sm-right m-0">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Profile</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content pb-4">
    <div class="container-fluid">
      <div class="row">

        <div class="col-lg-4 col-12">
          <div class="card card-outline card-danger" style="border-radius:14px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.06);">
            <div class="card-body text-center">
              <div style="width:120px; height:120px; border-radius:50%; overflow:hidden; border:4px solid rgba(220,53,69,0.15);" class="mx-auto mb-3">
                <img src="<?php echo htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%; height:100%; object-fit:cover;" alt="Admin profile">
              </div>

              <h4 class="font-weight-bold mb-0"><?php echo $admin ? htmlspecialchars($admin['username']) : htmlspecialchars($email); ?></h4>
              <div class="text-muted small mb-2"><?php echo $admin ? htmlspecialchars($admin['useremail']) : htmlspecialchars($email); ?></div>
              <span class="badge badge-danger px-3 py-2">ADMIN</span>

              <div class="mt-3">
                <button type="button" class="btn btn-danger btn-sm mr-1" data-toggle="modal" data-target="#editProfileModal">
                  <i class="fas fa-user-edit mr-1"></i> Edit Profile
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#changePasswordModal">
                  <i class="fas fa-user-lock mr-1"></i> Change Password
                </button>
              </div>

              <hr>

              <div class="text-left" style="font-size:0.95rem;">
                <div class="d-flex align-items-center justify-content-between py-1" style="border-bottom:1px solid #f0f0f0;">
                  <span class="text-muted"><i class="fas fa-id-badge mr-1"></i> User ID</span>
                  <strong><?php echo $admin ? (int)$admin['userid'] : '—'; ?></strong>
                </div>
                <div class="d-flex align-items-center justify-content-between py-1" style="border-bottom:1px solid #f0f0f0;">
                  <span class="text-muted"><i class="fas fa-phone mr-1"></i> Contact</span>
                  <strong><?php echo ($admin && $admin['usercontact'] !== '') ? htmlspecialchars($admin['usercontact']) : '—'; ?></strong>
                </div>
                <div class="d-flex align-items-center justify-content-between py-1" style="border-bottom:1px solid #f0f0f0;">
                  <span class="text-muted"><i class="fas fa-birthday-cake mr-1"></i> Age</span>
                  <strong><?php echo ($admin && $admin['userage'] !== null) ? (int)$admin['userage'] : '—'; ?></strong>
                </div>
                <div class="mt-2">
                  <div class="text-muted mb-1"><i class="fas fa-map-marker-alt mr-1"></i> Address</div>
                  <div class="font-weight-bold"><?php echo ($admin && $admin['useraddress'] !== '') ? nl2br(htmlspecialchars($admin['useraddress'])) : '—'; ?></div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="col-lg-8 col-12 mt-3 mt-lg-0">
          <div class="card" style="border-radius:14px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.06);">
            <div class="card-header bg-white" style="border-radius:14px 14px 0 0; border-bottom:1px solid #f0f0f0;">
              <h5 class="m-0 font-weight-bold"><i class="fas fa-clock text-danger mr-2"></i>Login / Logout Time</h5>
              <small class="text-muted">Tracked per admin account</small>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="small text-muted">Last Login</div>
                  <div class="font-weight-bold" style="font-size:1.05rem;"><?php echo htmlspecialchars($lastLogin); ?></div>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                  <div class="small text-muted">Last Logout</div>
                  <div class="font-weight-bold" style="font-size:1.05rem;"><?php echo htmlspecialchars($lastLogout); ?></div>
                </div>
              </div>
              <hr>
              <div class="text-muted small">
                Note: Logout time is recorded when you click Logout.
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<!-- EDIT PROFILE MODAL -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius:14px; overflow:hidden;">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white"><i class="fas fa-user-edit mr-2"></i>Edit Admin Profile</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="profile.php" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Full Name</label>
              <input type="text" class="form-control" name="username" value="<?php echo $admin ? htmlspecialchars($admin['username']) : ''; ?>" required>
            </div>
            <div class="form-group col-md-6">
              <label>Email</label>
              <input type="email" class="form-control" name="useremail" value="<?php echo $admin ? htmlspecialchars($admin['useremail']) : ''; ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Contact</label>
              <input type="text" class="form-control" name="usercontact" value="<?php echo $admin ? htmlspecialchars((string)$admin['usercontact']) : ''; ?>" placeholder="e.g. 09xxxxxxxxx">
            </div>
            <div class="form-group col-md-6">
              <label>Age</label>
              <input type="number" class="form-control" name="userage" min="0" max="120" value="<?php echo ($admin && $admin['userage'] !== null) ? (int)$admin['userage'] : ''; ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Address</label>
            <textarea class="form-control" name="useraddress" rows="3" placeholder="Complete address"><?php echo $admin ? htmlspecialchars((string)$admin['useraddress']) : ''; ?></textarea>
          </div>

          <div class="form-group">
            <label>Profile Photo</label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="userimage" name="userimage" accept="image/*">
              <label class="custom-file-label" for="userimage">Choose image</label>
            </div>
            <small class="text-muted">Max 1MB. Allowed: jpg, jpeg, png, gif.</small>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" name="btn_update_admin_profile" class="btn btn-danger"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius:14px; overflow:hidden;">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white"><i class="fas fa-user-lock mr-2"></i>Change Password</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="profile.php">
        <div class="modal-body">
          <div class="form-group">
            <label>Old Password</label>
            <input type="password" class="form-control" name="old_password" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" class="form-control" name="new_password" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" required>
          </div>
          <small class="text-muted">Tip: Use at least 4 characters.</small>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" name="btn_change_password" class="btn btn-danger"><i class="fas fa-save mr-1"></i> Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once 'footer.php'; ?>

<script>
$(function(){
  // show selected filename on custom file input
  $(document).on('change', '.custom-file-input', function(e){
    var fileName = e.target.files && e.target.files.length ? e.target.files[0].name : 'Choose image';
    $(this).next('.custom-file-label').html(fileName);
  });

  // auto open modal via query param
  var modal = <?php echo json_encode($autoModal); ?>;
  if(modal === 'edit'){
    $('#editProfileModal').modal('show');
  }
  if(modal === 'password'){
    $('#changePasswordModal').modal('show');
  }
});
</script>

<?php
if(isset($_SESSION['status']) && $_SESSION['status'] != ''):
?>
<script>
Swal.fire({
  icon: '<?php echo $_SESSION['status_code'] ?? 'info'; ?>',
  title: '<?php echo addslashes($_SESSION['status']); ?>',
  confirmButtonColor: '#3085d6'
});
</script>
<?php
  unset($_SESSION['status']);
  unset($_SESSION['status_code']);
endif;
?>
