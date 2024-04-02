<?php
ob_start();
session_start();
include('inc/header.php');
include('Inventory.php');

$verificationSuccess = false;
$verificationError = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $verificationCode = $_POST['verification_code'];
    $email = $_SESSION['verification_email']; // Retrieve the email from session variable
    
    $inventory = new Inventory();
    if ($inventory->verifyEmail($email, $verificationCode)) {
        $verificationSuccess = true;
    } else {
        $verificationError = "Invalid verification code. Please try again.";
    }
}
?>

<style>
html,
body,
body>.container {
    height: 95%;
    width: 100%;
}
body>.container {
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
}
#title{
    text-shadow:2px 2px 5px #000;
} 
</style>
<?php include('inc/container.php');?>

<h1 class="text-center my-4 py-3 text-light" id="title">Inventory Management System</h1>    
<div class="col-lg-4 col-md-5 col-sm-10 col-xs-12">
    <div class="card rounded-0 shadow">
        <div class="card-header">
            <div class="card-title h3 text-center mb-0 fw-bold">Email Verification</div>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <?php if ($verificationSuccess): ?>
                    <div class="alert alert-success rounded-0 py-1">Email verified successfully!</div>
                <?php elseif ($verificationError): ?>
                    <div class="alert alert-danger rounded-0 py-1"><?php echo $verificationError; ?></div>
                <?php endif; ?>
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="verification_code" class="control-label">Verification Code</label>
                        <input name="verification_code" id="verification_code" type="text" class="form-control rounded-0" placeholder="Enter verification code" autofocus="" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="verify" class="btn btn-primary rounded-0">Verify</button>
                    </div>
                </form>
                <?php if ($verificationSuccess || $verificationError): ?>
                <div class="d-grid mt-3">
                    <a href="login.php" class="btn btn-primary rounded-0">Back to Login</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>      

<?php include('inc/footer.php');?>
