<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "andes_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $_POST['name'];
$price = $_POST['price'];
$image = $_POST['image'];

$sql = "INSERT INTO products (name, price, image) VALUES ('$name', '$price', '$image')";

if ($conn->query($sql) === TRUE) {
    echo "Success";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
