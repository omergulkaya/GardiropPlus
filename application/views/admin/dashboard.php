<?php
$stats = isset($stats) ? $stats : [];
$recent_users = isset($recent_users) ? $recent_users : [];
$recent_clothing = isset($recent_clothing) ? $recent_clothing : [];
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-card-icon bg-primary me-3">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                    <small class="text-muted">Toplam Kullanıcı</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-card-icon bg-success me-3">
                    <i class="bi bi-bag"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['total_clothing'] ?? 0); ?></h3>
                    <small class="text-muted">Toplam Kıyafet</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-card-icon bg-info me-3">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['total_outfits'] ?? 0); ?></h3>
                    <small class="text-muted">Toplam Kombin</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-card-icon bg-warning me-3">
                    <i class="bi bi-person-check"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['active_users'] ?? 0); ?></h3>
                    <small class="text-muted">Aktif Kullanıcı</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="stat-card">
            <h5 class="mb-3"><i class="bi bi-clock-history"></i> Son Kullanıcılar</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>E-posta</th>
                            <th>Kayıt Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_users)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Henüz kullanıcı yok</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                    <td><?php echo isset($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="<?php echo base_url('admin/users'); ?>" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="stat-card">
            <h5 class="mb-3"><i class="bi bi-bag"></i> Son Eklenen Kıyafetler</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>İsim</th>
                            <th>Kategori</th>
                            <th>Eklenme Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_clothing)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Henüz kıyafet yok</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_clothing as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name'] ?? 'İsimsiz'); ?></td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? '-'); ?></td>
                                    <td><?php echo isset($item['date_added']) ? date('d.m.Y', strtotime($item['date_added'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="<?php echo base_url('admin/clothing'); ?>" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
            </div>
        </div>
    </div>
</div>

