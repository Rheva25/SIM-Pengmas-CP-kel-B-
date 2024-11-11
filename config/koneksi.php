<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "db_pengaduan";

try {
    $koneksi = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
    die();
}