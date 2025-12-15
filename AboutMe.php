<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Me</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            display: flex;
            flex-wrap: wrap;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            align-items: center;
        }

        .profile-image {
            flex: 1 1 250px;
            max-width: 250px;
            margin-right: 30px;
            text-align: center;
        }

        .profile-image img {
            width: 100%;
            border-radius: 15px;
            object-fit: cover;
        }

        .profile-info {
            flex: 2 1 400px;
        }

        h1 {
            margin-top: 0;
            color: #2c3e50;
        }

        p {
            line-height: 1.6;
            color: #444;
        }

        .info-item {
            margin-bottom: 10px;
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

        @media (max-width: 700px) {
            .container {
                flex-direction: column;
                align-items: center;
            }
            .profile-image {
                margin-right: 0;
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- Back to Home -->
    <a class="back-link" href="ArtPage.php">← Back to Home</a>

    <div class="profile-image">
        <img src="Me.jpeg" alt="Fatma Ali Abdourahmane">
    </div>

    <div class="profile-info">
        <h1>Fatma Ali Abdourahmane</h1>

        <p class="info-item"><strong>Major:</strong>MIS</p>
        <p class="info-item"><strong>Year:</strong> 3rd Year Student</p>
        <p class="info-item"><strong>Contact:</strong>fatma.abourahmane@ashesi.edu.gh</p>
        <p class="info-item"><strong>Passions:</strong> Art, Technology, Creative Design</p>

        <p>I like working on WebTech projects and exploring new ideas in both art and technology, I enjoy the creative work.</p>
    </div>

</div>

</body>
</html>
