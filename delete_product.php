<?php
session_start();
$serverName="LAPTOP-RVNVFIF2\SQLEXPRESS";
$connectionOptions=[
"Database"=>"SQLJourney",
"Uid"=>"",
"PWD"=>""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { 
    die(print_r(sqlsrv_errors(), true));
}
if(empty($_SESSION['cart'])) { header('Location: index.php'); exit; }

if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { header('Location: login.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
if($id) {
  $sql = "DELETE FROM STRBARAKSMENU WHERE PRODUCTID = '$id'";
  sqlsrv_query($conn, $sql);
}
header('Location: admin_dashboard.php'); exit;
