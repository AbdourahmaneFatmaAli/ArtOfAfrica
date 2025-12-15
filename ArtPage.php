<?php
session_start();
include 'db.php';


$username = '';

if(isset($_SESSION['user_id'])){
  $user_id= $_SESSION['user_id'];


  $stmt = $pdo->prepare('SELECT username FROM users WHERE user_id=?');
  $stmt->execute([$user_id]);
  $user = $stmt->fetch();

    if ($user) {
        $username = $user['username'];
    }

 
}



?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link
    href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css"
    rel="stylesheet"/>
    <link rel="stylesheet" href="style.css?v=125">
    
    
   
</head>
<body>
    <nav>
        
        <!-- check if the user is logged in -->

          <!-- If the user is logged in, display their username -->

          <?php if($username): ?>
            
            
            <!-- The links that are visible to everyone -->
            <li class="username-display">
              <a href="#"><?php echo htmlspecialchars($username); ?></a>
            </li>
          <?php endif; ?>
            <ul class="nav_links">
                <li class="link"><a href="ArtPage.php">Home</a></li>
                <li class="link"><a href="About.html">About</a></li>
                <li class="link"><a href="AboutMe.php">Logout</a></li>
         
          <?php if(!$username): ?>
            <li class="link"><a href="Index.php">Login</a></li>
            <li class="link"><a href="register.php">Sign Up</a></li>
          <?php else: ?>
             <!-- The links that are visible to only logged in people -->
            <li class='link'><a href='profile.php'>Profile</a></li>
            <li class='link'><a href='Architecture.php'>System Architecture</a></li>
            <li class="link"><a href="logout.php">Logout</a></li>
          <?php endif; ?>
        	

        </ul>
    </nav>
    <header>
        <div class="art-section">
            <div class="text">
                <h1>African Art</h1>
                <p>Welcome, artists and art lovers! Discover 
                  paintings and pieces of African art from talented creators around the world. 
                  Explore artworks, find inspiration, and share your own masterpieces.
                </p>
            </div>
        </div>

        <div class='scroll-down'>
          <span></span>
          <span></span>
          <span></span>
        </div>





                

        </div>
    </header>

    <section class="scrolling-art">
      <div class='title-wrapper'>
        <h2 class='animated-title'> Quelles merveilles ! </h2>


        <div class="art-row left-to-right">
            <img src="images.jpeg">
            <img src="image.jpeg">
            <img src="FLORES-DO-CARNAVAL-BY-CHRISTIAN-BEIJER-AFRICAN-ART-OAK-WOOD-PLAIN-FRAME.webp">
            <img src="71q3JR4ML0L._AC_UF1000,1000_QL80_.jpg">
            <img src="yinka-shonibare-woman-scaled.webp">
            <img src="desert.jpg">

            <img src="images.jpeg" alt="Art 1">
            <img src="image.jpeg" alt="Art 2">
            <img src="FLORES-DO-CARNAVAL-BY-CHRISTIAN-BEIJER-AFRICAN-ART-OAK-WOOD-PLAIN-FRAME.webp">
            <img src="71q3JR4ML0L._AC_UF1000,1000_QL80_.jpg">
            <img src="yinka-shonibare-woman-scaled.webp">
            <img src="desert.jpg">
            
        </div>
        <div class="art-row right-to-left">
            <img src="AfricanBlueHair_Final27thJuly.webp">
            <img src="art5.webp" alt="Art 2">
            <img src="art6.png" alt="Art 3">
            <img src="il_fullxfull.4238549742_bin4.webp">
            <img src="il_fullxfull.6583341529_lw1r.webp">
            <img src="Sun.webp">

            <img src="AfricanBlueHair_Final27thJuly.webp">
            <img src="art5.webp">
            <img src="art6.png">
            <img src="gbra,10x12,900x900.u4.webp">
            <img src="il_fullxfull.6583341529_lw1r.webp">
            <img src="Sun.webp">
        </div>
    </section>

    <section class='user-choice-section'>
      <div class='choice-card artist-card' onclick="location.href='upload_art.php'">
        <h2> Are you an Artist ?</h2>
        <p>Share your masterpieces with the world.</p>
        <button>Start Sharing</button>
      </div>

      <div class='choice-card artist-card' onclick="location.href='discover.php'">
        <h2> Are you an Art Lover ?</h2>
        <p>Discover artworks and talented creators.</p>
        <button>Explore Art</button>
      </div>

    </section>
      


</body>
</html>






   