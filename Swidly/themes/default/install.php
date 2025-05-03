<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/slate/bootstrap.min.css">
</head>
<body>
    <section>
        <div class="container mt-5">
            <h1 class="text-white">Swidly Installation</h1>
            <p class="mb-0">Welcome to the Swidly installation wizard. Please follow the steps below to complete the installation.</p>
            <strong class="mb-3 d-block">Thank you for purchasing Swidly.</strong>
            <div class="alert alert-info" role="alert">
                <strong>Note:</strong> This is a one-time installation process. After installation, you can access your application.
            </div>
            <div class="alert alert-danger mb-5" role="alert">
                <strong>Warning:</strong> Please ensure that you have a backup of your database before proceeding with the installation.
            </div>
            <?php
            require_once 'pre-install-check.php';
            
            // Add this at the beginning of your installation steps
            if ($_GET['step'] == 1 || !isset($_GET['step'])) :
                $checker = new PreInstallationChecker();
                $results = $checker->checkAll();
                
                if (!$checker->canProceed()): ?>
                    <style>
                        .requirements-check {
                            background: rgba(0,0,0,0.1);
                            padding: 20px;
                            border-radius: 8px;
                        }
                        .requirement-item {
                            margin: 8px 0;
                            padding: 8px;
                            background: rgba(255,255,255,0.05);
                            border-radius: 4px;
                        }
                        .text-success {
                            color: #28a745 !important;
                        }
                        .text-danger {
                            color: #dc3545 !important;
                        }
                    </style>
                    <h2 class="text-white">Pre-Installation Check</h2>
                    <div class="requirements-check">
                        <!-- PHP Version -->
                        <h3 class="h5 mt-4">PHP Version</h3>
                        <div class="requirement-item <?php echo $results['php_version']['status'] ? 'text-success' : 'text-danger'; ?>">
                            Required: <?php echo $results['php_version']['required']; ?><br>
                            Current: <?php echo $results['php_version']['current']; ?>
                        </div>
            
                        <!-- PHP Extensions -->
                        <h3 class="h5 mt-4">Required Extensions</h3>
                        <?php foreach ($results['extensions'] as $ext => $info): ?>
                            <div class="requirement-item <?php echo $info['status'] ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $ext; ?>: <?php echo $info['current']; ?>
                            </div>
                        <?php endforeach; ?>
            
                        <!-- Directory Permissions -->
                        <h3 class="h5 mt-4">Directory Permissions</h3>
                        <?php foreach ($results['permissions'] as $path => $info): ?>
                            <div class="requirement-item <?php echo $info['status'] ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $path; ?>: <?php echo $info['current']; ?>
                            </div>
                        <?php endforeach; ?>
            
                        <!-- Environment Checks -->
                        <h3 class="h5 mt-4">Environment Settings</h3>
                        <?php foreach ($results['env'] as $setting => $info): ?>
                            <div class="requirement-item <?php echo $info['status'] ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $setting; ?>: <?php echo $info['current']; ?> (Required: <?php echo $info['required']; ?>)
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="alert alert-danger mt-4 d-flex flex-column" role="alert">
                        <p>Please fix the above requirements before proceeding with the installation. If you are unsure how to fix these issues, please contact your hosting provider.</p>
                        <a href="?step=1" class="btn btn-primary">Retry</a>
                    </div>
                <?php else: ?>
                    <h2 class="text-white">Step 1: License Verification</h2>
                    <p>Please enter your purchase code to verify your license.</p>
                    <form method="POST" action="?step=2">
                        <div class="mb-3">
                            <label for="purchase_code" class="form-label">Purchase Code</label>
                            <input type="text" class="form-control" id="purchase_code" name="purchase_code" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Next</button>
                    </form>
                <?php endif; ?>             
            <?php elseif ($_GET['step'] == 2): ?>
                <?php
                // Verify the purchase code with your server
                if (empty($_POST['purchase_code'])) {
                    echo "<p class='alert alert-danger'>Please enter a purchase code.</p>";
                    echo "<a href='?step=1' class='btn btn-primary'>Try again</a>";
                    exit;
                }

                $response = checkPurchaseCode($_POST['purchase_code']);
                if ($response) {
                    \Swidly\Core\File::putFile('temp.php', "<?php\n\$purchase_code = '" . $_POST['purchase_code'] . "';\n?>");
                    // Proceed to the next step
                    echo "<h2 class='text-white'>Step 2: License Verified</h2>";
                    echo "<p class='alert alert-success'>License verified successfully.</p>";
                    echo "<p>Now, please proceed to the next step to configure your database.</p>";
                    echo "<a href='?step=3' class='btn btn-primary'>Next</a>";
                } else {
                    echo "<p class='alert alert-danger'>Invalid purchase code. Please try again.</p>";
                    echo "<a href='?step=1' class='btn btn-primary'>Try again</a>";
                }
                ?>
            <?php elseif ($_GET['step'] == 3) : ?>
                <?php
                // Check if the temp.php file exists
                if ($file = \Swidly\Core\File::readFile('temp.php')) {
                    // check if there a variable called purchase_code in the file
                    if (preg_match('/\$purchase_code\s*=\s*\'(.*?)\'/', $file, $matches)) {
                        $purchaseCode = $matches[1];
                        if (!checkPurchaseCode($purchaseCode)) {
                            echo "<p class='alert alert-danger'>Invalid purchase code. Please try again.</p>";
                            echo "<a href='?step=1' class='btn btn-primary'>Try again</a>";
                            exit;
                        }
                    } else {
                        echo "<p class='alert alert-danger'>Invalid purchase code. Please try again.</p>";
                        echo "<a href='?step=1' class='btn btn-primary'>Try again</a>";
                        exit;
                    }
                }
                ?>
                <h2>Site Configuration</h2>
                <form method="POST" action="?step=4">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="site_url" class="form-label">Site URL</label>
                        <input type="text" class="form-control" id="site_url" name="site_url" required>
                        <p class="form-text">Enter the full URL of your site.</p>
                    </div>
                    <div class="mb-3">
                        <label for="site_description" class="form-label">Site Description</label>
                        <input type="text" class="form-control" id="site_description" name="site_description">
                    </div>
                    <div class="mb-3">
                        <label for="site_keywords" class="form-label">Site Keywords</label>
                        <input type="text" class="form-control" id="site_keywords" name="site_keywords">
                        <p class="form-text">Separate keywords with commas.</p>
                    </div>
                    <div class="mb-3">
                        <label for="site_author" class="form-label">Site Author</label>
                        <input type="text" class="form-control" id="site_author" name="site_author">
                    </div>
                    <div class="mb-3">
                        <label for="site_email" class="form-label">Admin Email</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="site_phone" class="form-label">Admin Password</label>
                        <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                    </div>
                    <a href="?step=1" class="btn btn-secondary mr-3">Previous</a>&nbsp;&nbsp;<button type="submit" class="btn btn-primary">Next</button>
                </form>
            <?php elseif ($_GET['step'] == 4) : ?>
                <?php
                // Save site configuration to config.php
                $siteName = htmlspecialchars($_POST['site_name']);
                $siteUrl = htmlspecialchars($_POST['site_url']);
                $adminEmail = htmlspecialchars($_POST['admin_email']);
                $adminPass = htmlspecialchars($_POST['admin_pass']);
                $siteDescription = htmlspecialchars($_POST['site_description']);
                $siteKeywords = htmlspecialchars($_POST['site_keywords']);
                $siteAuthor = htmlspecialchars($_POST['site_author']);
                \Swidly\Core\File::putFile('config.php', "<?php\n\$site_name = '" . $siteName . "';\n\$site_url = '" . $siteUrl . "';\n\$admin_email = '" . $adminEmail . "';\n\$admin_pass = '" . $adminPass . "';\n\$site_description = '" . $siteDescription . "';\n\$site_keywords = '" . $siteKeywords . "';\n\$site_author = '" . $siteAuthor . "';\n?>");
                ?>
                <h2>Database Configuration</h2>
                <form method="POST" action="?step=5">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Database Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Database User</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Database Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                    </div>
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Database Name</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Next</button>
                </form>
            <?php elseif ($_GET['step'] == 5) : ?>
                <?php
                // Save database configuration to config.php
                $dbHost = htmlspecialchars($_POST['db_host']);
                $dbUser = htmlspecialchars($_POST['db_user']);
                $dbPass = htmlspecialchars($_POST['db_pass']);
                $dbName = htmlspecialchars($_POST['db_name']);

                if ($file = \Swidly\Core\File::readFile('temp.php')) {
                    // check if there a variable called purchase_code in the file
                    if (preg_match('/\$purchase_code\s*=\s*\'(.*?)\'/', $file, $matches)) {
                        $purchaseCode = $matches[1];
                    }
                }

                require_once 'config.php';
                
                \Swidly\Core\File::putFile('config.php', "<?php\n\$site_name = '" . $siteName . "';\n\$site_url = '" . $siteUrl . "';\n\$admin_email = '" . $adminEmail . "';\n\$admin_pass = '" . $adminPass . "';\n\$site_description = '" . $siteDescription . "';\n\$site_keywords = '" . $siteKeywords . "';\n\$site_author = '" . $siteAuthor . "';\n?>");
                \Swidly\Core\File::putFile('db.php', "<?php\n\$db_host = '" . $dbHost . "';\n\$db_user = '" . $dbUser . "';\n\$db_pass = '" . $dbPass . "';\n\$db_name = '" . $dbName . "';\n?>");
                \Swidly\Core\File::putFile('installed.php', "<?php\n\$purchase_code = '" . $purchaseCode . "';\n?>");
                // Check if the database connection is successful
                try {
                    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    echo "<p class='alert alert-danger'>Database connection failed: " . $e->getMessage() . "</p>";
                    echo "<a href='?step=3' class='btn btn-primary'>Try again</a>";
                    exit;
                }

                // Create the database if it doesn't exist
                try {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
                    $pdo->exec("USE `$dbName`");
                } catch (PDOException $e) {
                    echo "<p class='alert alert-danger'>Failed to create database: " . $e->getMessage() . "</p>";
                    echo "<a href='?step=3' class='btn btn-primary'>Try again</a>";
                    exit;
                }

                $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    site_name VARCHAR(255) NOT NULL,
                    site_url VARCHAR(255) NOT NULL,
                    site_description TEXT DEFAULT NULL,
                    site_keywords TEXT DEFAULT NULL,
                    site_author VARCHAR(255) DEFAULT NULL,
                    admin_email VARCHAR(255) NOT NULL,
                    theme VARCHAR(255) DEFAULT 'default',
                    default_lang VARCHAR(10) DEFAULT 'en',
                    url VARCHAR(255) DEFAULT '',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $site_description = 'Your site description here';
                $site_keywords = 'Your site keywords here, separated by commas';
                $site_author = 'Your name here';
                $stmt = $pdo->prepare("INSERT INTO settings (site_name, site_url, site_description, site_keywords, site_author, admin_email, theme, default_lang, url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$site_name, $site_url, $site_description, $site_keywords, $site_author, $admin_email, 'default', 'en', '']);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$site_name, $admin_email, password_hash($adminPass, PASSWORD_BCRYPT)]);

                runSqlFile('https://meebeestudio.com/api/finish-install?code=' . $purchaseCode, $pdo);
                
                ?>

                <h2>Installation Complete</h2>
                <p>Your application has been installed successfully.</p>
                <p><a href="../index.php">Go to the homepage</a></p>
            <?php endif; ?>
        </div>
    </section>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>