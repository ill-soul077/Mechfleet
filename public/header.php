<?php
require_once __DIR__ . '/../includes/util.php';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?>Mechfleet</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css" />
  <script defer src="js/main.js"></script>
</head>
<body>
  <header class="topbar" role="banner">
    <button class="sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="true" aria-controls="sidebar" id="sidebarToggle">
      ☰
    </button>
    <div class="brand" role="heading" aria-level="1">Mechfleet</div>
    <nav class="topnav" aria-label="Top Navigation">
      <a href="index.php">Dashboard</a>
      <a href="work_orders.php">Work Orders</a>
      <a href="products.php">Inventory</a>
      <a href="services.php">Services</a>
      <a href="customers.php">Customers</a>
      <a href="mechanics.php">Mechanics</a>
      <a href="reports.php">Reports</a>
    </nav>
    <div class="topbar-actions">
      <a href="logout.php">Logout</a>
    </div>
  </header>

  <div class="layout">
    <aside id="sidebar" class="sidebar" role="navigation" aria-label="Sidebar">
      <div class="sidebar-section">
        <a class="sidebar-link" href="index.php">Dashboard</a>
        <a class="sidebar-link" href="work_orders.php">Work Orders</a>
        <a class="sidebar-link" href="products.php">Inventory</a>
        <a class="sidebar-link" href="services.php">Services</a>
        <a class="sidebar-link" href="customers.php">Customers</a>
        <a class="sidebar-link" href="mechanics.php">Mechanics</a>
        <a class="sidebar-link" href="sql_demos.php">SQL Demos</a>
        <a class="sidebar-link" href="setops_subqueries.php">Set Ops & Subqueries</a>
        <a class="sidebar-link" href="reports.php">Reports</a>
        <a class="sidebar-link" href="#">Settings</a>
      </div>
    </aside>
    <main id="main" class="main" tabindex="-1">
      <div class="container">
