<?php 
ob_start();
session_start();
include('inc/header.php');
include('Inventory.php');

$registrationSuccess = false;
$registrationError = '';

// Include PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Function to generate random verification code
function generateVerificationCode($length = 6) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

// Function to send email
function sendEmail($email, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP(); 
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true; 
        $mail->Username   = 'attendance940@gmail.com';
        $mail->Password   = 'hcprkfqfeblmbdzu ';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;                                    
        
        //Recipients
        $mail->setFrom('attendance940@gmail.com', 'Admin');
        $mail->addAddress($email);   
        $mail->addReplyTo('attendance940@gmail.com', 'Admin'); 
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true; // Email sent successfully
    } catch (Exception $e) {
        return false; // Error sending email
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = htmlspecialchars($_POST['name']); // Encode name input
    $email = htmlspecialchars($_POST['email']); // Encode email input
    $password = htmlspecialchars($_POST['pwd']); // Encode password input
    
    // Validate input (you may add more validations)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registrationError = "Invalid email address!";
    } elseif (empty($name) || empty($password)) {
        $registrationError = "Name and password are required!";
    } else {
        // Register user and send verification email
        $inventory = new Inventory();
        $result = $inventory->register($name, $email, $password);
        
        if ($result === true) {
            // Send verification email
            $subject = 'Verify Your Email Address';
            $verificationCode = generateVerificationCode();
           
			$message = 'Your verification code is: ' . $verificationCode;
            if (sendEmail($email, $subject, $message)) {
                // Set verification email session variable
                $_SESSION['verification_email'] = $email;
                // Redirect to verification.php
                header("Location: verification.php");
                exit(); 
            } else {
                $registrationError = "Error sending verification email.";
            }
        } else {
            $registrationError = $result;
        }
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
            <div class="card-title h3 text-center mb-0 fw-bold">Register</div>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <?php if ($registrationSuccess): ?>
                    <div class="alert alert-success rounded-0 py-1">Registration successful. Please check your email for verification.</div>
                <?php elseif ($registrationError): ?>
                    <div class="alert alert-danger rounded-0 py-1"><?php echo $registrationError; ?></div>
                <?php endif; ?>
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="name" class="control-label">Name</label>
                        <input name="name" id="name" type="text" class="form-control rounded-0" placeholder="Your name" autofocus="" value="<?= isset($_POST['name']) ? $_POST['name'] : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="control-label">Email</label>
                        <input name="email" id="email" type="email" class="form-control rounded-0" placeholder="Email address" value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="control-label">Password</label>
                        <input type="password" class="form-control rounded-0" id="password" name="pwd" placeholder="Password" required>
                    </div>  
                    <div class="d-grid">
                        <button type="submit" name="register" class="btn btn-primary rounded-0">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>      

<?php include('inc/footer.php');?>
