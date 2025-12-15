<?php
session_start();
include 'db.php';


$message = '';

// checking the form was submitted via POST
if($_SERVER['REQUEST_METHOD'] == 'POST') {

// Get username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];
     // Check if "Remember Me" checkbox is checked
    $remember = isset($_POST['remember']);
    
    $stmt = $pdo->prepare("SELECT user_id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {

        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $username;
    
 // Remember Me functionality with cookies so the server will remember the next time and cookies are set for 30mdays 
            if($remember) {
                setcookie('username', $username, time() + (86400 * 30), '/');
                setcookie('password', $password, time() + (86400 * 30), '/');
            } else {
                setcookie('username', '', time() - 3600, '/');
                setcookie('password', '', time() - 3600, '/');
            }   

            echo '<span style="color:green;">Login successful! Start your journey in this Gallery, Redirecting......</span>';
        } else {
            echo '<span style="color:red;">Incorrect password! Try again.</span>';
        }
    } else {
        echo '<span style="color:red;">Username not found!</span>';
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="Lstyles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <div class="wrapper">
        <form id="loginForm" method='POST'>
            <h1>Login</h1>

            <div id='message' style='text-align:center; margin-bottom:10px;'></div>

            <div class="input-box">

                <input type="text" name='username' placeholder= 'Username' value= "<?php echo isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']):  ''; ?>" required>
                <i class='bx bxs-user'></i> 
            </div>
            <div class='input-box'>
                <input type='password' name='password' id='password' placeholder= 'Password' required>
                <i class ='bx bx-show' id='togglePassword'></i>
            </div>

            <div class="remember-me">
                <label><input type="checkbox" name= "remember" 
                    <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>> Remember Me</label>
            </div>

           <button type="submit" class="btn">Login</button>
            <div class="register-link">
                <p>Dont have an account? <a href="register.php">Register</a></p>
            </div>
        </form>
    </div>

    <script>
         // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', () => {
        const pw = document.getElementById('password');
        pw.type = pw.type === 'password' ? 'text' : 'password';
    });

// AJAX form submission so the page does not need to reload
    document.getElementById('loginForm').addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);

        fetch('', { 
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            document.getElementById('message').innerHTML = data;
            if(data.includes('Login successful')){
                setTimeout(() => { window.location.href = 'ArtPage.php';}, 1500);
            }
        })
        .catch(err => console.log(err));
    });
    </script>
    
</body>
</html>
