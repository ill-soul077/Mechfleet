<?php
require_once __DIR__ . '/../includes/util.php';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?>Mechfleet</title>
  <link rel="stylesheet" href="css/style.css" />
  <script defer src="js/main.js"></script>
</head>
<body>
  <header>
    <div class="container">
      <h1 style="margin: .5rem 0;">Mechfleet</h1>
      <nav>
        <a href="index.php">Home</a> ·
        <a href="sql_demos.php">SQL Demo</a>
      </nav>
    </div>
  </header>
  <main>
    <div class="container">
