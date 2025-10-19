<?php
require_once __DIR__ . '/../includes/util.php';
?><!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?>Mechfleet</title>
  <link rel="stylesheet" href="css/style.css" />
  <script defer src="js/main.js"></script>
</head>
<body>
  <header style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 1rem;background:#f6f6f6;border-bottom:1px solid #ddd;">
    <div>
      <strong>Mechfleet</strong>
    </div>
    <nav style="display:flex;gap:1rem;flex-wrap:wrap;">
      <a href="index.php">Home</a>
      <a href="customers.php">Customers</a>
      <a href="vehicles.php">Vehicles</a>
      <a href="mechanics.php">Mechanics</a>
      <a href="services.php">Services</a>
      <a href="products.php">Products</a>
      <a href="work_orders.php">Work Orders</a>
  <a href="reports.php">Reports</a>
      <a href="income.php">Payments</a>
      <a href="sql_demos.php">SQL Demos</a>
    </nav>
    <div>
      <a href="logout.php">Logout</a>
    </div>
  </header>
  <main>
    <div class="container">
