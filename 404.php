<?php
// Include header
include('inc/header.php');
?>

<style>
    .error-container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .error-content {
        text-align: center;
    }
</style>

<!-- Custom 404 error page content -->
<section class="error-container">
    <div class="error-content">
        <h1 class="error-title">Oops! 404 - Page Not Found</h1>
      <p class="error-message">The page you are looking for may have been removed, had its name changed, or is temporarily unavailable.</p>
        <p><img src="images/404.gif" alt="404 Error Image" class="error-image">          </p>
        <p><a href="index.php" class="btn btn-primary">Go to Home Page</a></p>
    </div>
</section>

<?php
// Include footer
include('inc/footer.php');
?>
