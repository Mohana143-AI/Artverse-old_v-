<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Base URL configuration for reliable pathing
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/ArtSphere/');
}

// Generic page meta data (can be overridden in individual pages)
$page_title = $page_title ?? 'ArtSphere | Your Idea. Your Creator. Your Creation.';
$meta_desc = $meta_desc ?? 'Discover local talent. Customize every detail. Create something uniquely yours.';

$css_version = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS (Dark mode natively enabled) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Phosphor Icons (Modern icon set) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Custom CSS with cache-busting -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= $css_version ?>">

    <!-- Critical Global Overrides (Prevents any white backgrounds across all pages) -->
    <style>
        :root, [data-bs-theme="dark"] {
            --bg-dark: #080808;
            --bg-card: #121212;
            --border-color: #242424;
            --accent-pink: #F20A68;
            --accent-hover: #c9075a;
            --bs-body-bg: #080808 !important;
            --bs-body-color: #f5f5f5 !important;
            --bs-card-bg: #121212 !important;
            --bs-card-border-color: #242424 !important;
            --bs-border-color: #242424 !important;
        }

        body {
            background-color: #080808 !important;
            color: #f5f5f5 !important;
            font-family: 'Outfit', sans-serif !important;
        }

        /* Enforce dark backgrounds on ALL cards, containers, tables across all pages */
        .card, 
        .card-body, 
        .card-header, 
        .card-footer, 
        .bg-card, 
        .modal-content, 
        .dropdown-menu,
        .table,
        .table-dark {
            background-color: #121212 !important;
            border-color: #242424 !important;
            color: #f5f5f5 !important;
        }

        /* Buttons: Ensure high visibility and pink hover on all pages */
        .btn-outline-custom {
            background-color: rgba(242, 10, 104, 0.12) !important;
            border: 1.5px solid #F20A68 !important;
            color: #ffffff !important;
            font-weight: 500 !important;
            transition: all 0.25s ease !important;
        }
        .btn-outline-custom:hover,
        .btn-outline-custom:focus {
            background-color: #F20A68 !important;
            border-color: #F20A68 !important;
            color: #ffffff !important;
            box-shadow: 0 0 16px rgba(242, 10, 104, 0.5) !important;
            transform: translateY(-2px) !important;
        }

        .btn-outline-secondary,
        .btn-outline-light,
        .btn-outline-dark,
        .btn-secondary,
        .btn-dark,
        .btn-light {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid #383838 !important;
            color: #ffffff !important;
            transition: all 0.25s ease !important;
        }
        .btn-outline-secondary:hover,
        .btn-outline-light:hover,
        .btn-outline-dark:hover,
        .btn-secondary:hover,
        .btn-dark:hover,
        .btn-light:hover {
            background-color: #F20A68 !important;
            border-color: #F20A68 !important;
            color: #ffffff !important;
            box-shadow: 0 0 16px rgba(242, 10, 104, 0.45) !important;
            transform: translateY(-2px) !important;
        }

        .btn-primary,
        .btn-primary-custom {
            background-color: #F20A68 !important;
            border-color: #F20A68 !important;
            color: #ffffff !important;
            transition: all 0.25s ease !important;
        }
        .btn-primary:hover,
        .btn-primary-custom:hover {
            background-color: #c9075a !important;
            border-color: #c9075a !important;
            color: #ffffff !important;
            box-shadow: 0 0 16px rgba(242, 10, 104, 0.5) !important;
            transform: translateY(-2px) !important;
        }
    </style>
</head>
<body>
