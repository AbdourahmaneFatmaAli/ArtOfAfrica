<?php session_start(); ?>
<!DOCTYPE html>
<html lang='en'>
<head>

    <meta charset='UTF-8'>
    <title>Sytem Architecture</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 40px;

        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;

        }
        h1, h2{
            color: #2c3e50;
        }
        pre {
            padding: 15px;
            border-radius: 8px;

        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<div class="container">
    <!-- Back to home -->
    <a href="ArtPage.php">← Back to Home</a>
    
    <h1>System Architecture</h1>

    <h2>Architecture Type</h2>

    <p>
        This project follows a Three-Tier Web Architecture consiting of: Frontend, backend, and Database layers.

    </p>

    <h2>Architecture Diagram</h2>
    <pre>
Client (browser)
    ↓
Apache Web Server
(PHP Backend)
    ↓
MySQL Database
    </pre>

    <h2>Frontend Layer</h2>
    <p>
        Built using HTML, CSS, and JavaScript. It handles user interaction,
        form validation, and data submission using HTTP requests.
    </p>

    <h2>Backend Layer</h2>
    <p>
        Developed using PHP. It handles authentication, session management,
        input validation, and all CRUD operations using PDO.
    </p>

    <h2>Database Layer</h2>
    <p>
    <ul>
        <li>users</li>
        <li>artworks</li>
        <li>comments</li>
        <li>likes</li>
    </ul>
    

    <h2>Data Flow Example (Signup)</h2>
    <p>
        User submits signup form → PHP validates input → Password is hashed →
        Data is stored in MySQL → User redirected to login page.
    </p>

    <h3>User Login</h3>
    <p>
        User submits login form → PHP verifies credentials →
        Session is created → User redirected to dashboard/home page.
    </p>

    <h3>Upload Artwork</h3>
    <p>
        Logged-in user uploads artwork →
        PHP validates file and data →
        Image stored on server →
        Artwork details stored in database.

    </p>

    <h3>Like and Comment</h3>
    <p>
        User clicks like or submits comment →
        PHP checks user session →
        Data stored in likes or comments table →
        Page updates dynamically.
    </p>

</div>
</body>
</html>
