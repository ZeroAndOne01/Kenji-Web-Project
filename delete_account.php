<?php
session_start();

$serverName="LAPTOP-RVNVFIF2\SQLEXPRESS";
$connectionOptions=[
"Database"=>"SQLJourney",
"Uid"=>"",
"PWD"=>""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];

$sql = "DELETE FROM USERS WHERE USERID = $id";
$result = sqlsrv_query($conn, $sql);

header("Location: accounts.php");
exit;
    