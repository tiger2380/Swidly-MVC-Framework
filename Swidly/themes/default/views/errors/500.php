<?php dump(get_defined_vars()) ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            text-align: center;
            color: white;
            max-width: 600px;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            line-height: 1;
            text-shadow: 4px 4px 8px rgba(0,0,0,0.3);
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .error-description {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .btn-home {
            background-color: white;
            color: #667eea;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
            color: #667eea;
        }
        .error-details {
            background-color: rgba(0,0,0,0.2);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 2rem;
            text-align: left;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <div class="error-message">Oops! Something went wrong</div>
        <div class="error-description">
            We're sorry, but something unexpected happened on our server. 
            Our team has been notified and is working to fix the issue.
        </div>
        <a href="<?= htmlspecialchars($homeUrl ?? '/') ?>" class="btn-home">
            ← Back to Homepage
        </a>
        
        <?php if (!empty($debugMode) && !empty($errorMessage)): ?>
        <div class="error-details">
            <strong>Error Details (Debug Mode):</strong><br>
            <?= htmlspecialchars($errorMessage) ?>
            
            <?php if (!empty($errorFile)): ?>
                <br><br><strong>File:</strong> <?= htmlspecialchars($errorFile) ?>
            <?php endif; ?>
            
            <?php if (!empty($errorLine)): ?>
                <br><strong>Line:</strong> <?= htmlspecialchars($errorLine) ?>
            <?php endif; ?>
            
            <?php if (!empty($errorTrace)): ?>
                <br><br><strong>Stack Trace:</strong>
                <pre style="margin: 0; white-space: pre-wrap;"><?= htmlspecialchars($errorTrace) ?></pre>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
