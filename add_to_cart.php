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

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $sql = "SELECT PRODUCTID, PRODUCTNAME, PRICE, IMAGEPATH FROM STRBARAKSMENU WHERE PRODUCTID = '$id'";
    $result = sqlsrv_query($conn, $sql);
    if($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $item = [
            'id' => $row['PRODUCTID'],
            'name' => $row['PRODUCTNAME'],
            'price' => (float)$row['PRICE'],
            'img' => $row['IMAGEPATH'],
            'qty' => 1
        ];
        if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $found = false;
        foreach($_SESSION['cart'] as &$c) {
            if($c['id'] == $item['id']) { $c['qty'] += 1; $found = true; break; }
        }
        if(!$found) $_SESSION['cart'][] = $item;
    }
}

$redirect = isset($_POST['return']) ? $_POST['return'] : 'index.php';
header("Location: $redirect");
exit;
