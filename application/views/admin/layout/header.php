<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Admin Panel'; ?> - GardıropPlus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.css" rel="stylesheet">
    <?php
    // CSS/JS Optimizasyonu: Production'da birleştirilmiş ve minify edilmiş versiyonları kullan
    if (ENVIRONMENT === 'production') {
        // Production: Birleştirilmiş ve minify edilmiş dosya
        $combined_css = base_url('application/views/admin/assets/admin-combined.min.css');
        if (file_exists(FCPATH . 'application/views/admin/assets/admin-combined.min.css')) {
            echo '<link href="' . $combined_css . '" rel="stylesheet">';
        } else {
            // Fallback: Ayrı dosyalar (minify edilmiş)
            $css_suffix = '.min.css';
            echo '<link href="' . base_url('application/views/admin/assets/design-tokens' . $css_suffix) . '" rel="stylesheet">';
            echo '<link href="' . base_url('application/views/admin/assets/admin-theme' . $css_suffix) . '" rel="stylesheet">';
            echo '<link href="' . base_url('application/views/admin/assets/modern-admin' . $css_suffix) . '" rel="stylesheet">';
        }
    } else {
        // Development: Ayrı dosyalar (debug için)
        echo '<link href="' . base_url('application/views/admin/assets/design-tokens.css') . '" rel="stylesheet">';
        echo '<link href="' . base_url('application/views/admin/assets/admin-theme.css') . '" rel="stylesheet">';
        echo '<link href="' . base_url('application/views/admin/assets/modern-admin.css') . '" rel="stylesheet">';
    }
    ?>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Ana içeriğe geç</a>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-box-seam"></i> 
            <span>GardıropPlus</span>
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
    
    <main id="main-content" class="main-content" role="main">
        <nav class="navbar">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link d-md-none sidebar-toggle me-3" type="button">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h4 class="mb-0"><?php echo isset($title) ? $title : 'Admin Panel'; ?></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo base_url('admin/dashboard'); ?>">Dashboard</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Global Search -->
                    <div class="global-search">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Ara... (Ctrl+K)" aria-label="Search">
                        </div>
                    </div>
                    <span class="d-none d-md-inline">Hoş geldiniz, <strong><?php echo isset($admin['first_name']) ? $admin['first_name'] : 'Admin'; ?></strong></span>
                    <a href="<?php echo base_url('admin/logout'); ?>" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Çıkış</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Theme Toggle Button -->
        <button class="theme-toggle" aria-label="Toggle theme">
            <i class="bi bi-moon"></i>
        </button>

