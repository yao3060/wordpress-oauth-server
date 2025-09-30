<?php
/**
 * Simple test page for OAuth2 authorization form
 */

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['approve'])) {
        echo "<p><strong>Approve button was clicked!</strong></p>";
    } elseif (isset($_POST['deny'])) {
        echo "<p><strong>Deny button was clicked!</strong></p>";
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>OAuth2 Form Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .button { padding: 10px 20px; margin: 10px; cursor: pointer; }
        .button-primary { background: #0073aa; color: white; border: none; }
        .button-secondary { background: #f1f1f1; color: #333; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>OAuth2 Authorization Form Test</h1>
    
    <p>This is a test to see if form submission works correctly.</p>
    
    <form method="post" action="">
        <input type="hidden" name="test_nonce" value="abc123">
        
        <div>
            <button type="submit" name="approve" value="1" class="button button-primary">
                Authorize
            </button>
            
            <button type="submit" name="deny" value="1" class="button button-secondary">
                Deny
            </button>
        </div>
    </form>
    
    <h3>Debug Info:</h3>
    <p><strong>REQUEST_METHOD:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></p>
    <p><strong>REQUEST_URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
    <p><strong>SCRIPT_NAME:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></p>
</body>
</html>
