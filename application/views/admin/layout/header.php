<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Admin Panel'; ?> - GardıropPlus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 250px;
        }
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem 0;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar-brand {
            padding: 1rem 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li {
            margin: 0.25rem 0;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar-menu a i {
            width: 20px;
            margin-right: 0.75rem;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            padding: 1rem 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-box-seam"></i> GardıropPlus
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo base_url('admin/dashboard'); ?>" class="<?php echo (uri_string() == 'admin/dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/users'); ?>" class="<?php echo (strpos(uri_string(), 'admin/users') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i> Kullanıcılar
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/clothing'); ?>" class="<?php echo (uri_string() == 'admin/clothing') ? 'active' : ''; ?>">
                    <i class="bi bi-bag"></i> Kıyafetler
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/outfits'); ?>" class="<?php echo (uri_string() == 'admin/outfits') ? 'active' : ''; ?>">
                    <i class="bi bi-grid-3x3-gap"></i> Kombinler
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/statistics'); ?>" class="<?php echo (uri_string() == 'admin/statistics') ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up"></i> İstatistikler
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/reports'); ?>" class="<?php echo (strpos(uri_string(), 'admin/reports') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-bar-graph"></i> Raporlar
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/errors'); ?>" class="<?php echo (strpos(uri_string(), 'admin/error') !== false && strpos(uri_string(), 'admin/error_statistics') === false) ? 'active' : ''; ?>">
                    <i class="bi bi-bug"></i> API Hataları
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/error_statistics'); ?>" class="<?php echo (strpos(uri_string(), 'admin/error_statistics') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart"></i> Hata İstatistikleri
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/activity_logs'); ?>" class="<?php echo (strpos(uri_string(), 'admin/activity_logs') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history"></i> Aktivite Logları
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/twofa_settings'); ?>" class="<?php echo (strpos(uri_string(), 'admin/twofa') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-shield-lock"></i> 2FA Ayarları
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/data_retention'); ?>" class="<?php echo (strpos(uri_string(), 'admin/data_retention') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-database-check"></i> Veri Saklama
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/privacy_settings'); ?>" class="<?php echo (strpos(uri_string(), 'admin/privacy_settings') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-eye-slash"></i> Gizlilik Ayarları
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/settings'); ?>" class="<?php echo (uri_string() == 'admin/settings') ? 'active' : ''; ?>">
                    <i class="bi bi-gear"></i> Ayarlar
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('admin/logout'); ?>">
                    <i class="bi bi-box-arrow-right"></i> Çıkış
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <nav class="navbar">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h4 class="mb-0"><?php echo isset($title) ? $title : 'Admin Panel'; ?></h4>
                <div class="d-flex align-items-center">
                    <span class="me-3">Hoş geldiniz, <strong><?php echo isset($admin['first_name']) ? $admin['first_name'] : 'Admin'; ?></strong></span>
                    <a href="<?php echo base_url('admin/logout'); ?>" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Çıkış
                    </a>
                </div>
            </div>
        </nav>

