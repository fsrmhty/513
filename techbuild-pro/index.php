<?php
require_once 'config/session.php';
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Welcome to TechBuild Pro</h1>
    <p class="page-subtitle">Your one-stop shop for all PC building and repair needs</p>
</div>

<div class="stats-grid" style="max-width: 800px; margin: 3rem auto;">
    <div class="stat-card text-center">
        <h3>High-Quality Components</h3>
        <p>⚡</p>
        <p>Premium PC parts from trusted brands</p>
    </div>
    <div class="stat-card text-center">
        <h3>Custom PC Building</h3>
        <p>🛠️</p>
        <p>Expert-built systems tailored to your needs</p>
    </div>
    <div class="stat-card text-center">
        <h3>Reliable Repairs</h3>
        <p>🔧</p>
        <p>Fast and professional repair services</p>
    </div>
</div>

<div class="text-center mt-4">
    <a href="/techbuild-pro/products/" class="btn btn-primary btn-lg">Browse Products & Services</a>
</div>

<?php include 'includes/footer.php'; ?>