<?php
ob_start();
session_start();
include('inc/header.php');
include('Inventory.php');
include('logging.php');

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['pwd']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $timestamp = date('Y-m-d H:i:s');
    
    // Validate input (you may add more validations)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginError = "Invalid email address!";
    } else {
        // Attempt login
        $inventory = new Inventory();
        $user = $inventory->login($email, $password);
        
   
			if ($user) {
       // Login successful, set session variables
       $_SESSION['email'] = $user[0]['email']; // Corrected variable name
       $_SESSION['userid'] = $user[0]['userid'];
       $_SESSION['name'] = $user[0]['name'];            
       header("Location:index.php");
       exit(); // Prevent further execution
        
        } else {
            // Login failed
            $loginError = "Invalid email or password!";
            // Log failed login attempt
            Logger::logFailedLogin($email, $ip, $userAgent, $timestamp);
        }
    }
    
    // Log login attempt regardless of success or failure
    Logger::logLoginAttempt($email, $ip, $userAgent, $timestamp);
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
            <div class="card-title h3 text-center mb-0 fw-bold">Login</div>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <form method="post" action="">
                    <div class="form-group">
                    <?php if ($loginError ) { ?>
                        <div class="alert alert-danger rounded-0 py-1"><?php echo $loginError; ?></div>
                    <?php } ?>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="control-label">Email</label>
                        <input name="email" id="email" type="email" class="form-control rounded-0" placeholder="Email address" autofocus="" value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="control-label">Password</label>
                        <input type="password" class="form-control rounded-0" id="password" name="pwd" placeholder="Password" required>
                    </div>  
                    <div class="d-grid">
                        <button type="submit" name="login" class="btn btn-primary rounded-0">Login</button>
                        <a href="register.php" class="btn btn-secondary rounded-0">Register</a> <!-- Added Register button -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>      
<?php include('inc/footer.php');?>
