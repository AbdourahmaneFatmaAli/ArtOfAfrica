<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Index.php");
    exit;
}

$user_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT username, first_name, last_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user){
    die('User not found');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);


    

    $stmt = $pdo->prepare("UPDATE users set username = ?, first_name=?, last_name=?, email=? WHERE user_id= ?");
    $stmt->execute([$username, $first_name, $last_name, $email, $user_id]);

   $_SESSION['username'] = $username;

    header("Location: profile.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile</title>
<style>



.edit-container {
    max-width: 400px;
    margin: 50px auto;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.edit-container h1{
    text-align: center;
    margin-bottom: 20px;
}

.edit-container input {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
}


.btn-submit {
    width: 100%;
    padding: 10px;
    background-color: #5b21b6;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

.btn-submit:hover {
    background-color: #4c1d95;
}

.back-link {
    display: block;
    text-align: center;
    margin-top: 15px;
    text-decoration: none;
    color: #5b21b6;
}
.background-images {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height:100%;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    opacity: 0.50;
    z-index: -1;
    

}
.two-images img, .background-images img{
    width: 40%;
    height: auto;
    object-fit: cover;
}

</style>




</head>
<body>
    <div class='background-images'>
        <img src='africa.webp' alt='African Art'>
        <img src='africa.webp' alt='African Art'>
    </div>
<div class="edit-container">
    <h1>Edit Profile</h1>

   
    <form method="POST">
        <input type='text' name="username" placeholder='Username' value='<?php echo htmlspecialchars($user['username']); ?>'required>
        <input type='text' name="first_name" placeholder='First Name' value='<?php echo htmlspecialchars($user['first_name']); ?>'required>
        <input id="last_name" type="text" name="last_name" placeholder= 'Last Name' value="<?= htmlspecialchars($user['last_name']); ?>" required>
        <input id="email" type="email" name="email" placeholder= 'Email' value="<?= htmlspecialchars($user['email']); ?>" required>
        <button type="submit" class="btn-submit">Save Changes</button>

    </form>
   

    <a href="profile.php" class="back-link">Back to Profile</a>
</div>

 

</body>
</html>
