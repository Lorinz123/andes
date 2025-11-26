<?php
$conn = new mysqli("localhost", "root", "", "andes_db");
$result = $conn->query("SELECT * FROM products");

$products = [];
while ($row = $result->fetch_assoc()) {

    // FIX: auto-correct image path (your DB probably only stores filename)
    if (!str_starts_with($row["image"], "../")) {
        $row["image"] = "../assets/image/" . $row["image"];
    }

    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shop</title>
  <link rel="stylesheet" href="../assets/style/styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    .stock-level {
      color: #333;
      font-weight: 600;
      font-size: 14px;
      margin: 5px 0;
    }
    .stock-level.out {
      color: red;
    }
    .flying-img {
      position: fixed;
      z-index: 9999;
      width: 100px;
      height: auto;
      border-radius: 10px;
      transition: all 0.8s ease-in-out;
      pointer-events: none;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar">
    <div class="navdiv">
      <div class="logo">
        <a href="../index.php"><img src="../assets/image/logo.png" alt="logo" /></a>
        <a href="../index.php"><p>FLOWER PUFF</p></a>
      </div>
      <ul>
        <li><a href="../index.php">Home</a></li>

        <!-- FIXED: shop.php not shop.html -->
        <li><a href="./shop.php">Shop</a></li>

        <li><a href="./about.php">About</a></li>
        <li>
          <a href="./cart.html" class="cart-link">
            <i class="fa-solid fa-cart-shopping cart-icon"></i>
            <span id="cart-count"></span>
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- Main Content -->
  <main>
    <section class="shop-section">
      <div class="shop-logo">
        <img src="../assets/image/logo.png" alt="logo" />
        <div class="shop-div1">
          <h1>CATEGORIES</h1>
          <ul>
            <li><input type="checkbox" />Flame</li>
            <li><input type="checkbox" />Daydream</li>
            <li><input type="checkbox" />Pink Passion</li>
            <li><input type="checkbox" />Sunnies</li>
            <li><input type="checkbox" />Glam</li>
            <li><input type="checkbox" />Taupe</li>
            <li><input type="checkbox" />Pink Tulips</li>
            <li><input type="checkbox" />White Tulips</li>
            <li><input type="checkbox" />Unchanted Love</li>
            <li><input type="checkbox" />Purple Tulips</li>
            <li><input type="checkbox" />Charming</li>
            <li><input type="checkbox" />Snow</li>
          </ul>
        </div>
      </div>

      <div class="shop-sales">
        <ul id="shopList"></ul>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer>
    <section class="section4">
      <div class="footer-menu">
        <p>Copyrights 2025. Flower Puff’s Management. All Rights Reserved.</p>
      </div>
    </section>
  </footer>

  <!-- Script -->
<script>
// LOAD PRODUCTS FROM MYSQL via AJAX so the page always shows current DB state
let products = [];

async function loadProducts() {
  try {
    const resp = await fetch('../api/get_products.php');
    const data = await resp.json();
    // normalize image paths (DB may store only filename)
    products = data.map(row => {
      if (!row.image || !row.image.startsWith('../')) {
        row.image = '../assets/image/' + row.image;
      }
      return row;
    });
    renderShop();
  } catch (err) {
    console.error('Failed to load products:', err);
    shopList.innerHTML = '<p>Failed to load products.</p>';
  }
}

// DOM
const shopList = document.getElementById("shopList");

// RENDER PRODUCTS (same as before)
function renderShop() {
  shopList.innerHTML = "";

  if (products.length === 0) {
    shopList.innerHTML = "<p>No products available.</p>";
    return;
  }

  products.forEach((p, index) => {
    const li = document.createElement("li");
    li.innerHTML = `
      <img src="${p.image}" alt="${p.name}">
      <p>${p.name}</p>
      <p>₱${p.price}</p>
      <p class="stock-level ${p.stock <= 0 ? "out" : ""}">
        ${p.stock > 0 ? `In Stock: ${p.stock} pcs` : "Out of Stock"}
      </p>
      <button class="buy-btn" 
        ${p.stock <= 0 ? "disabled" : ""}
        onclick="addToCart(${index}, this)">
        Add to Cart
      </button>
    `;
    shopList.appendChild(li);
  });
}

// ADD TO CART (same as before)
function addToCart(index, button) {
  const product = products[index];
  if (!product || product.stock <= 0) return;

  product.stock--;

  // update local storage cart
  let cart = JSON.parse(localStorage.getItem("cart")) || [];
  const existing = cart.find(item => item.name === product.name);

  if (existing) existing.quantity++;
  else cart.push({ 
    name: product.name, 
    price: product.price, 
    image: product.image, 
    quantity: 1 
  });

  localStorage.setItem("cart", JSON.stringify(cart));

  // animation
  const img = button.closest("li").querySelector("img");
  animateToCart(img);

  renderShop();
  updateCartCount();
}

// Flying animation
function animateToCart(img) {
  const cartIcon = document.querySelector(".cart-icon");
  if (!cartIcon || !img) return;

  const flyingImg = img.cloneNode(true);
  flyingImg.classList.add("flying-img");
  document.body.appendChild(flyingImg);

  const imgRect = img.getBoundingClientRect();
  const cartRect = cartIcon.getBoundingClientRect();

  flyingImg.style.left = imgRect.left + "px";
  flyingImg.style.top = imgRect.top + "px";

  requestAnimationFrame(() => {
    flyingImg.style.transform = `translate(${cartRect.left - imgRect.left}px, ${cartRect.top - imgRect.top}px) scale(0.1)`;
    flyingImg.style.opacity = "0";
  });

  flyingImg.addEventListener("transitionend", () => flyingImg.remove());
}

// UPDATE CART COUNT
function updateCartCount() {
  const cart = JSON.parse(localStorage.getItem("cart")) || [];
  const total = cart.reduce((sum, i) => sum + i.quantity, 0);
  document.getElementById("cart-count").textContent = total;
}

// START
loadProducts();
updateCartCount();

// NOTE: stock updates to the server are not implemented here yet.
// If you want purchases to decrement stock on the server, I can add an API call.
</script>

</body>
</html>
