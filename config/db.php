<?php
// config/db.php
// ini file buat nyambungin web kita ke database MySQL pake mysqli bawaan PHP

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'dochelp_db';

// buka koneksi ke database
$conn = new mysqli($host, $user, $password, $dbname);

// kalo gagal konek, stop script terus tampilin error
if ($conn->connect_error) {
    die("Aduh gagal konek ke database nih: " . $conn->connect_error);
}

// set charset biar support huruf apa aja & ga error pas render teks
$conn->set_charset("utf8mb4");
