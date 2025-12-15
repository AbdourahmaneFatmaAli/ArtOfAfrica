<?php
include 'db.php';

$message = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {


    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];

      // Validate email format for 
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<span style="color:red;">Invalid email format!</span>';
        exit;
    }
      // email should have a valid end like .com, .org, .gh

    if (!preg_match('/\.[A-Za-z]{2,}$/', $email)) {
    echo '<span style="color:red;">Email must contain a valid domain (e.g .com, .org, .gh)</span>';
        exit;
    }
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // validating the format of the password
    if ($password !== $confirm) {
        echo '<span style="color:red;">Passwords do not match!</span>';
        exit;
    } elseif (!preg_match('/[A-Za-z]/', $password)) {
        echo '<span style ="color:red;">Password must contain at least one letter.';
        exit;
    }elseif (!preg_match('/[0-9]/', $password)) {
        echo '<span style ="color:red;">Password must contain at least one number.';
        exit;
    }elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        echo '<span style ="color:red;">Password must contain at least one special character (@, #, %, !.).</span>';
        exit;
    }elseif (strlen( $password) < 8) {
        echo '<span style ="color:red;">Password must be at least 8 characters long. (@, #, %, !.).</span>';
        exit;
    } 

    // for security password is hashed 
    $hashed = password_hash($password, PASSWORD_DEFAULT);

        try {
            //inserting new user
            $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)");

            $stmt->execute([$first_name, $last_name, $username, $email, $hashed]);
            
            echo '<span style="color:green;">Welcome, Aesthete! You are registered successfully! You can login now!</span>';
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'username') !== false) {
                echo '<span style= "color: red;">Not welcomed, username already exists!</span>';
            } elseif (strpos($e->getMessage(), 'email') !== false) {
                echo '<span style="color:red;">Email already exists!</span>';
            } else {
                echo '<span style="color:red;">Error: ' .$e->getMessage(). '</span>';
            }
                
            
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
    <link rel="stylesheet" href="Rstyles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <div class="wrapper">
        <form id="registerForm" method='POST'>
            <h1>Register</h1>

            <div id='message' style='text-align:center; margin-bottom:10px;'></div>

            <div class="input-box">
                <input type="text" name="first_name" placeholder="First Name" required>
                <i class='bx bxs-user'></i> 
            </div>
            <div class="input-box">
                <input type="text" name="last_name" placeholder="Last Name" required>
                <i class='bx bxs-user'></i> 
            </div>
            <div class="input-box">
                <input type="text" name= 'username' placeholder="Username" required>
                <i class='bx bxs-user'></i> 
            </div>
            <div class="input-box">
                <input type='email' name='email' placeholder="Email" required>
                <i class='bx bxs-envelope'></i> 
            </div>
            <div class="input-box">
                <input type="password" name= 'password' id='password' placeholder="Password" required>
                <i class='bx bx-show' id='togglePassword'></i>
            </div>
             <div class="input-box">
                <input type="password" name= 'confirm' id='confirm' placeholder="Confirm Password" required>
                <i class='bx bx-show' id='toggleConfirm'></i>
            </div>
    
            <button type="submit" class="btn">Register</button>
            <div class="register-link">
                <p>Already have an account? <a href="index.php">Login</a> </p>
            </div>
        </form>
    </div>

        <script>

        document.getElementById('togglePassword').addEventListener('click', ()=> {
            const pw = document.getElementById('password');
            pw.type = pw.type === 'password' ? 'text' : 'password';

        });

        document.getElementById('toggleConfirm').addEventListener('click', () => {
            const cpw = document.getElementById('confirm');
            cpw.type = cpw.type === 'password' ? 'text' : 'password';
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('register.php', {
                method: 'POST',
                body: formData
            })

            .then(res => res.text())
            .then(data => {
                document.getElementById('message').innerHTML = data;

                if(data.includes('Welcome, Aesthete! You are registered successfully!')){
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                    ;
                }
            })
            
        });


        

</script>
</body>
</html>



