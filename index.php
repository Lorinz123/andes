<?php
// --- MySQL Connection ---
$servername = "localhost";
$username = "root";       // default XAMPP username
$password = "";           // default empty password
$dbname = "andes_db";     // change this to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/style/styles.css">
    <title>FLOWER PUFF</title>

    <!-- Your JS -->
    <script src="./assets/js/script.js" defer></script>
</head>

<body>

<nav class="navbar">
    <div class="navdiv">
        <div class="logo">
            <a href="index.php"><img src="./assets/image/logo.png" alt="logo"></a>
            <a href="index.php"><p>FLOWER PUFF</p></a>
        </div>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="./pages/shop.php">Shop</a></li>
            <li><a href="./pages/about.php">About</a></li>
        </ul>
    </div>
</nav>

<header class="home-banner">
    <main>

        <section class="section1">
            <div class="section1-div">
                <div class="section1-div2">
                    <h1>BEAUTY BLOOM</h1>
                    <p>SELECT FROM OUR CURATED COLLECTION OF FRESH AND TASTEFUL ARRANGEMENTS OF FLOWERS</p>
                    <a href="./pages/shop.php"><button>SHOP NOW</button></a>
                </div>
            </div>
        </section>

        <section class="section2">
            <div class="section2-div">
              <h2>CELEBRATING MEMORIES</h2>
              <p>Heartfelt Celebrations, both Big and Small, made personal and memorable.</p>
            </div>

            <div class="section2-img">
                <img class="img1" src="./assets/image/flower-1.jpg" alt="flower">
                <img class="img2" src="./assets/image/flower-2.jpg" alt="flower 2">
                <img class="img3" src="./assets/image/flower-3.jpg" alt="flower 3">
            </div>

            <div class="section2-button2">
                <a href="./pages/shop.php"><button>SHOP WITH FLOWER PUFF</button></a>
            </div>
        </section>

        <section class="section3">
            <div class="section3-div">
                <h3>ROMANCE REIMAGINED</h3>
                <p>Intimate Unions Elevated with the touch of floral perfection</p>
                <a href="./pages/shop.php"><button>WEDDINGS AT FLOWER PUFF</button></a>
            </div>
        </section>

    </main>
</header>

<footer>
  <div class="section4">
    <p class="footer-text">Copyrights 2025. Flower Puff’s Management. All Rights Reserved.</p>

    <div class="footer-right">
      <button class="admin-btn" onclick="window.location.href='./pages/admin-login.php'">Admin</button>
    </div>
  </div>
</footer>

</body>
</html>
