<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "andes_db");

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Handle API requests
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// If request body is JSON (application/json), the action may come from the JSON payload.
$rawInput = file_get_contents("php://input");
$jsonInput = null;
if (!$action && $rawInput) {
    $jsonInput = json_decode($rawInput, true);
    if (is_array($jsonInput) && isset($jsonInput['action'])) {
        $action = $jsonInput['action'];
    }
}

// For API calls, suppress PHP warnings so we always return valid JSON
if ($action) {
  ini_set('display_errors', '0');
  error_reporting(0);
}

if ($action === 'get_products') {
  $result = $conn->query("SELECT id, name, price, stock, image FROM products");
  if ($result === false) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "DB query error: " . $conn->error]);
    exit;
  }
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Auto-correct image path
        if (!str_starts_with($row["image"], "../")) {
            $row["image"] = "../assets/image/" . $row["image"];
        }
        $products[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($products);
    exit;
}

if ($action === 'add_product') {
    $data = json_decode(file_get_contents("php://input"), true);
    $name = $conn->real_escape_string($data['name']);
    $price = floatval($data['price']);
    $stock = intval($data['stock']);
    $image = $conn->real_escape_string($data['image']);

    $query = "INSERT INTO products (name, price, stock, image) VALUES ('$name', $price, $stock, '$image')";
    if ($conn->query($query)) {
    $insert_id = $conn->insert_id;
    // Fetch the inserted row to return it to the client for immediate UI update
    $res = $conn->query("SELECT id, name, price, stock, image FROM products WHERE id=$insert_id");
    $row = null;
    if ($res && $res->num_rows > 0) {
      $row = $res->fetch_assoc();
      if (!str_starts_with($row["image"], "../")) {
        $row["image"] = "../assets/image/" . $row["image"];
      }
    }
    header('Content-Type: application/json');
    echo json_encode(["success" => true, "id" => $insert_id, "row" => $row]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => $conn->error]);
    }
    exit;
}

if ($action === 'edit_product') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);
    $name = $conn->real_escape_string($data['name']);
    $price = floatval($data['price']);
    $stock = intval($data['stock']);
    $image = $conn->real_escape_string($data['image']);

    $query = "UPDATE products SET name='$name', price=$price, stock=$stock, image='$image' WHERE id=$id";
    if ($conn->query($query)) {
        header('Content-Type: application/json');
        echo json_encode(["success" => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => $conn->error]);
    }
    exit;
}

if ($action === 'delete_product') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);

    $query = "DELETE FROM products WHERE id=$id";
    if ($conn->query($query)) {
        header('Content-Type: application/json');
        echo json_encode(["success" => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => $conn->error]);
    }
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="../assets/style/admin.css">
</head>
<body>
  <header>
    <h1>Admin Panel</h1>

    <button id="backBtn" onclick="window.location.href='../index.php'">← Back to home</button>
  </header>

  <main>
    <section class="admin-card">
      <h2>Manage Products</h2>
      <button id="addProductBtn">+ Add Product</button>

      <table id="productTable">
        <thead>
          <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </section>

    <section class="form-card" id="productFormSection">
      <h2 id="formTitle">Add Product</h2>
      <form id="productForm">
        <input type="hidden" id="productId">
        <label>Name:</label>
        <input type="text" id="productName" required>

        <label>Price:</label>
        <input type="number" id="productPrice" required>

        <label>Stock:</label>
        <input type="number" id="productStock" required>

        <label>Image URL:</label>
        <input type="text" id="productImage" placeholder="Paste image link here" required>

        <button type="submit" id="saveProductBtn">Save</button>
      </form>
    </section>
  </main>

  <script>
    let products = [];

    const productTable = document.querySelector("#productTable tbody");
    const addProductBtn = document.getElementById("addProductBtn");
    const formSection = document.getElementById("productFormSection");
    const form = document.getElementById("productForm");
    const formTitle = document.getElementById("formTitle");
    const productId = document.getElementById("productId");

    // Form inputs
    const productName = document.getElementById("productName");
    const productPrice = document.getElementById("productPrice");
    const productStock = document.getElementById("productStock");
    const productImage = document.getElementById("productImage");

    // Load products from MySQL
    async function loadProducts() {
      try {
        const response = await fetch('admin.php?action=get_products');
        const text = await response.text();

        // Try parse JSON and handle errors returned by server
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error('Invalid JSON from get_products:', text);
          alert('Failed to load products: server returned invalid response (see console)');
          return;
        }

        if (data && data.error) {
          console.error('API error get_products:', data.error);
          alert('Failed to load products: ' + data.error);
          return;
        }

        products = data;
        renderProducts();
      } catch (error) {
        console.error('Error loading products:', error);
        alert('Failed to load products (network error)');
      }
    }

    function renderProducts() {
      productTable.innerHTML = "";
      if (products.length === 0) {
        productTable.innerHTML = "<tr><td colspan='5'>No products available.</td></tr>";
        return;
      }

      products.forEach((p) => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td><img src="${p.image}" alt="${p.name}"></td>
          <td>${p.name}</td>
          <td>₱${parseFloat(p.price).toFixed(2)}</td>
          <td>${p.stock}</td>
          <td>
            <button class="edit" onclick="editProduct(${p.id})">Edit</button>
            <button class="delete" onclick="deleteProduct(${p.id})">Delete</button>
          </td>
        `;
        productTable.appendChild(row);
      });
    }

    // Add new product button
    addProductBtn.addEventListener("click", () => {
      form.reset();
      productId.value = "";
      formTitle.textContent = "Add Product";
      formSection.scrollIntoView({ behavior: "smooth" });
    });

    // Form submission
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const productData = {
        name: productName.value,
        price: parseFloat(productPrice.value),
        stock: parseInt(productStock.value),
        image: productImage.value
      };

      try {
        let response;
        if (productId.value === "") {
          // Add new product
          response = await fetch('admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add_product', ...productData })
          });
        } else {
          // Edit existing product
          productData.id = parseInt(productId.value);
          response = await fetch('admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'edit_product', ...productData })
          });
        }

        const text = await response.text();
        let result;
        try {
          result = JSON.parse(text);
        } catch (e) {
          console.error('Non-JSON response from save:', text);
          alert('Failed to save product: server returned invalid response (see console)');
          return;
        }

        if (result.success) {
          alert(productId.value === "" ? "Product added!" : "Product updated!");
          form.reset();
          productId.value = "";
          // If server returned the newly inserted row, append it locally to avoid another request
          if (result.row) {
            products.push(result.row);
            renderProducts();
          } else {
            loadProducts();
          }
        } else {
          console.error('Save error:', result);
          alert("Error saving product: " + (result.error || "Unknown error"));
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to save product');
      }
    });

    // Edit product
    function editProduct(id) {
      const product = products.find(p => p.id === id);
      if (product) {
        productName.value = product.name;
        productPrice.value = product.price;
        productStock.value = product.stock;
        productImage.value = product.image;
        productId.value = id;
        formTitle.textContent = "Edit Product";
        formSection.scrollIntoView({ behavior: "smooth" });
      }
    }

    // Delete product
    async function deleteProduct(id) {
      if (confirm("Delete this product?")) {
        try {
          const response = await fetch('admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_product', id: id })
          });

          const text = await response.text();
          let result;
          try { result = JSON.parse(text); } catch(e) {
            console.error('Non-JSON response from delete:', text);
            alert('Failed to delete product: server returned invalid response (see console)');
            return;
          }

          if (result.success) {
            alert("Product deleted!");
            loadProducts();
          } else {
            console.error('Delete error:', result);
            alert("Error deleting product: " + (result.error || "Unknown error"));
          }
        } catch (error) {
          console.error('Error:', error);
          alert('Failed to delete product');
        }
      }
    }

    // Initialize
    loadProducts();
  </script>
</body>
</html>
