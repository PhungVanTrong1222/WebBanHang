<?php
session_start();
$_SESSION['cart'] = ['8' => ['quantity' => 1]];
echo json_encode($_SESSION['cart']) . PHP_EOL;
$productId = (int)'8';
unset($_SESSION['cart'][$productId]);
echo json_encode($_SESSION['cart']) . PHP_EOL;
