<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            color: #f5576c;
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
            color: #f5576c;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <div class="error-message">Page Not Found</div>
        <div class="error-description">
            The page you're looking for doesn't exist or has been moved.
            Let's get you back on track!
        </div>
        <a href="<?= htmlspecialchars($homeUrl ?? '/') ?>" class="btn-home">
            ← Back to Home
        </a>
    </div>
</body>
</html>
