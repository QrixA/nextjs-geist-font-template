<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Server Error - SakuraCloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-code">500</div>
        <h1>Server Error</h1>
        <p>Something went wrong on our end. Please try again later.</p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">Return Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
    </div>

    <style>
    .error-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        padding: 2rem;
    }

    .error-container {
        max-width: 500px;
        text-align: center;
        background: #fff;
        padding: 3rem 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .error-code {
        font-size: 6rem;
        font-weight: 700;
        color: #000;
        line-height: 1;
        margin-bottom: 1rem;
    }

    .error-container h1 {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: #000;
    }

    .error-container p {
        color: #666;
        margin-bottom: 2rem;
    }

    .error-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    @media (max-width: 640px) {
        .error-actions {
            flex-direction: column;
        }

        .error-actions .btn {
            width: 100%;
        }
    }
    </style>
</body>
</html>
