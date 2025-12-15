<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT username, first_name, last_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found.');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <style>

body {
    font-family: Arial, sans-serif;
    background: #faf7f2;
    margin: 0;
    padding: 0;
    height: 100vh;
    overflow: hidden;
    position: relative
}
.blob{

    position: absolute;
    border-radius: 50%;
    opacity: 0.7;
    mix-blend-mode: multiply;
    animation: move 50s infinite alternate;
}

.blob1 {
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, #ff6b6b, #feca57);
    top: -100px;
    left: -50px;
}

.blob2{
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, #1dd1a1, #54a0ff);
    top: 100px;
    right: -150px;
}

.blob3 {
    width: 350px;
    height: 350px;
    background: linear-gradient(135deg, #5f27cd, #ff9ff3);
    bottom: -150px;
    left: 50px
}
@keyframes move{
    0% {transform: translate(0,0) rotate(0deg); }
    50% {transform: translate(50px, -50px) rotate(45deg); }
    100%{transform: translate(-50px, 50px) rotate(-45deg); }
}


.profile-wrapper {
    max-width: 450px;
    margin: 60px auto;
    background: white;
    padding: 30px 35px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    text-align: center;
    position: relative;
    z-index: 10;
}

.profile-wrapper h1 {
    font-size: 28px;
    color: #5b21b6;
    margin-bottom: 20px;
}

.profile-pic {
    font-size: 90px;
    color: #5b21b6;
    margin-bottom: 10px;
}

.info-box {
    text-align: left;
    background: #f3f0ff;
    padding: 15px 20px;
    border-radius: 10px;
    margin-top: 20px;
}

.info-box p {
    font-size: 16px;
    margin: 8px 0;
    color: #333;
}

.btn-area {
    margin-top: 25px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.btn-area a {
    display: block;
    text-decoration: none;
    padding: 12px;
    background: #5b21b6;
    color: white;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.3s;
}

.btn-area a:hover {
    background: #4c1d95;
}

.home-link {
    background: #e5e0ff !important;
    color: #4c1d95 !important;
}

.home-link:hover {
    background: #d6ccff !important;
}

</style>
</head>
<body>

<div class='blob blob1'></div>
<div class='blob blob2'></div>
<div class='blob blob3'></div>

    <div class='profile-wrapper'>
        <div class='profile-pic'>
            <i class='ri-user-3-fill'></i>
        </div>

           
        <h1>My profile</h1>

        <div class='info-box'>
            <p><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
            <p><strong>Name:</strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
        </div>
    
        <div class='btn-area'>
            <a href='edit_profile.php'>Edit Profile</a>
            <a href='ArtPage.php' class='home-link'> Return to Home</a>
            <a href='logout.php' style="backgroud:red">Logout</a>
        </div>
    </div>


</body>
</html>

