<?php
$stats = isset($stats) ? $stats : [];
$recent_users = isset($recent_users) ? $recent_users : [];
$recent_clothing = isset($recent_clothing) ? $recent_clothing : [];
$error_widget = isset($error_widget) ? $error_widget : [];
?>

<!-- API Hata Özeti Widget -->
<?php if (!empty($error_widget)): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <?php $this->load->view('admin/components/error_widget', $error_widget); ?>
    </div>
</div>
<?php endif; ?>

<!-- Modern Stats Grid -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div class="flex-grow-1">
                    <p class="text-muted mb-2 small text-uppercase fw-semibold">Toplam Kullanıcı</p>
                    <h3 class="mb-0"><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                    <small class="text-success">
                        <i class="bi bi-arrow-up"></i> Aktif
                    </small>
                </div>
                <div class="stat-card-icon bg-primary">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div class="flex-grow-1">
                    <p class="text-muted mb-2 small text-uppercase fw-semibold">Toplam Kıyafet</p>
                    <h3 class="mb-0"><?php echo number_format($stats['total_clothing'] ?? 0); ?></h3>
                    <small class="text-info">
                        <i class="bi bi-bag"></i> Kayıtlı
                    </small>
                </div>
                <div class="stat-card-icon bg-success">
                    <i class="bi bi-bag"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div class="flex-grow-1">
                    <p class="text-muted mb-2 small text-uppercase fw-semibold">Toplam Kombin</p>
                    <h3 class="mb-0"><?php echo number_format($stats['total_outfits'] ?? 0); ?></h3>
                    <small class="text-primary">
                        <i class="bi bi-grid-3x3-gap"></i> Oluşturuldu
                    </small>
                </div>
                <div class="stat-card-icon bg-info">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div class="flex-grow-1">
                    <p class="text-muted mb-2 small text-uppercase fw-semibold">Aktif Kullanıcı</p>
                    <h3 class="mb-0"><?php echo number_format($stats['active_users'] ?? 0); ?></h3>
                    <small class="text-warning">
                        <i class="bi bi-person-check"></i> Online
                    </small>
                </div>
                <div class="stat-card-icon bg-warning">
                    <i class="bi bi-person-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern Activity Cards -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-clock-history text-primary me-2"></i> 
                    Son Kullanıcılar
                </h5>
                <a href="<?php echo base_url('admin/users'); ?>" class="btn btn-sm btn-outline-primary">
                    Tümünü Gör <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <?php if (empty($recent_users)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0">Henüz kullanıcı yok</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_users as $user): ?>
                    <div class="list-group-item border-0 px-0 py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-3">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">
                                    <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="bi bi-envelope me-1"></i>
                                    <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">
                                    <?php echo isset($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '-'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-bag text-success me-2"></i> 
                    Son Eklenen Kıyafetler
                </h5>
                <a href="<?php echo base_url('admin/clothing'); ?>" class="btn btn-sm btn-outline-primary">
                    Tümünü Gör <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <?php if (empty($recent_clothing)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0">Henüz kıyafet yok</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_clothing as $item): ?>
                    <div class="list-group-item border-0 px-0 py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-3 bg-success">
                                <i class="bi bi-bag-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">
                                    <?php echo htmlspecialchars($item['name'] ?? 'İsimsiz'); ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="bi bi-tag me-1"></i>
                                    <?php echo htmlspecialchars($item['category'] ?? '-'); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">
                                    <?php echo isset($item['date_added']) ? date('d.m.Y', strtotime($item['date_added'])) : '-'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

