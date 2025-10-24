<?php
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get user info from session (assuming auth stores username)
$username = $_SESSION['username'] ?? 'Admin';
$user_initials = strtoupper(substr($username, 0, 1));
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?>Mechfleet</title>
  
  <!-- Bootstrap 5.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  
  <!-- Toastr for notifications -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  
  <!-- Custom Modern CSS -->
  <link rel="stylesheet" href="css/modern.css">
  
  <!-- Chart.js for dashboard -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- Sidebar Navigation -->
<aside class="mf-sidebar" id="mfSidebar">
  <!-- Brand -->
  <div class="mf-sidebar-brand">
    <i class="fas fa-wrench"></i>
    <span class="mf-brand-text">Mechfleet</span>
  </div>
  
  <!-- Navigation -->
  <nav class="mf-sidebar-nav">
    <!-- Main Section -->
    <div class="mf-nav-section">
      <div class="mf-nav-title">Main</div>
      <a href="index.php" class="mf-nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </a>
    </div>
    
    <!-- Management Section -->
    <div class="mf-nav-section">
      <div class="mf-nav-title">Management</div>
      <a href="customers.php" class="mf-nav-link <?= $current_page === 'customers.php' ? 'active' : '' ?>">
        <i class="fas fa-users"></i>
        <span>Customers</span>
      </a>
      <a href="vehicles.php" class="mf-nav-link <?= $current_page === 'vehicles.php' ? 'active' : '' ?>">
        <i class="fas fa-car"></i>
        <span>Vehicles</span>
      </a>
      <a href="mechanics.php" class="mf-nav-link <?= $current_page === 'mechanics.php' ? 'active' : '' ?>">
        <i class="fas fa-user-cog"></i>
        <span>Mechanics</span>
      </a>
      <a href="work_orders.php" class="mf-nav-link <?= $current_page === 'work_orders.php' ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i>
        <span>Work Orders</span>
      </a>
    </div>
    
    <!-- Inventory Section -->
    <div class="mf-nav-section">
      <div class="mf-nav-title">Inventory</div>
      <a href="products.php" class="mf-nav-link <?= $current_page === 'products.php' ? 'active' : '' ?>">
        <i class="fas fa-boxes"></i>
        <span>Parts & Products</span>
      </a>
      <a href="services.php" class="mf-nav-link <?= $current_page === 'services.php' ? 'active' : '' ?>">
        <i class="fas fa-tools"></i>
        <span>Services</span>
      </a>
    </div>
    
    <!-- Reports Section -->
    <div class="mf-nav-section">
      <div class="mf-nav-title">Analytics</div>
      <a href="reports.php" class="mf-nav-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i>
        <span>Reports</span>
      </a>
      <a href="income.php" class="mf-nav-link <?= $current_page === 'income.php' ? 'active' : '' ?>">
        <i class="fas fa-dollar-sign"></i>
        <span>Income</span>
      </a>
    </div>
  </nav>
</aside>

<!-- Main Content Area -->
<div class="mf-main-content">
  <!-- Top Header -->
  <header class="mf-top-header">
    <div class="mf-header-content">
      <!-- Left Section -->
      <div class="mf-header-left">
        <!-- Sidebar Toggle -->
        <button class="mf-toggle-btn" id="sidebarToggle" aria-label="Toggle Sidebar">
          <i class="fas fa-bars"></i>
        </button>
      </div>
      
      <!-- Right Section -->
      <div class="mf-header-right">
        <!-- Notifications (placeholder) -->
        <button class="mf-toggle-btn" aria-label="Notifications" title="Notifications">
          <i class="fas fa-bell"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; display: none;">
            0
          </span>
        </button>
        
        <!-- User Menu -->
        <div class="dropdown">
          <button class="mf-user-menu" id="userMenuToggle" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="mf-user-avatar"><?= e($user_initials) ?></div>
            <div class="d-none d-md-block">
              <div class="fw-semibold" style="font-size: 0.9rem; color: #495057;"><?= e($username) ?></div>
              <div style="font-size: 0.75rem; color: #6c757d;">Administrator</div>
            </div>
            <i class="fas fa-chevron-down" style="font-size: 0.75rem; color: #6c757d;"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 200px;">
            <li>
              <h6 class="dropdown-header">
                <i class="fas fa-user-circle me-2"></i><?= e($username) ?>
              </h6>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="index.php">
                <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="event.preventDefault(); alert('Profile settings coming soon!');">
                <i class="fas fa-user-cog me-2 text-info"></i>Profile Settings
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </header>
  
  <!-- Breadcrumb -->
  <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
  <nav aria-label="breadcrumb" class="px-4 py-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <?php foreach ($breadcrumbs as $label => $url): ?>
        <?php if ($url): ?>
          <li class="breadcrumb-item"><a href="<?= e($url) ?>"><?= e($label) ?></a></li>
        <?php else: ?>
          <li class="breadcrumb-item active" aria-current="page"><?= e($label) ?></li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ol>
  </nav>
  <?php endif; ?>
  
  <!-- Page Content -->
  <div class="container-fluid px-4 py-4">
