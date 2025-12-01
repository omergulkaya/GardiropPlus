<?php
$this->load->helper('privacy');
$errors = isset($errors) ? $errors : [];
$filters = isset($filters) ? $filters : [];
$stats = isset($stats) ? $stats : [];
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-card-icon bg-danger me-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['total'] ?? 0); ?></h3>
                    <small class="text-muted">Toplam Hata</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-card-icon bg-warning me-3">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['last_24h'] ?? 0); ?></h3>
                    <small class="text-muted">Son 24 Saat</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-card-icon bg-danger me-3">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['critical'] ?? 0); ?></h3>
                    <small class="text-muted">Kritik Hatalar</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtreler -->
<div class="stat-card mb-4">
    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filtreler</h5>
    <form method="get" action="<?php echo base_url('admin/errors'); ?>">
        <div class="row">
            <div class="col-md-2">
                <label class="form-label">Status Code</label>
                <select name="status_code" class="form-select">
                    <option value="">Tümü</option>
                    <option value="400" <?php echo (isset($filters['status_code']) && $filters['status_code'] == '400') ? 'selected' : ''; ?>>400 - Bad Request</option>
                    <option value="401" <?php echo (isset($filters['status_code']) && $filters['status_code'] == '401') ? 'selected' : ''; ?>>401 - Unauthorized</option>
                    <option value="403" <?php echo (isset($filters['status_code']) && $filters['status_code'] == '403') ? 'selected' : ''; ?>>403 - Forbidden</option>
                    <option value="404" <?php echo (isset($filters['status_code']) && $filters['status_code'] == '404') ? 'selected' : ''; ?>>404 - Not Found</option>
                    <option value="500" <?php echo (isset($filters['status_code']) && $filters['status_code'] == '500') ? 'selected' : ''; ?>>500 - Server Error</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <option value="">Tümü</option>
                    <option value="new" <?php echo (isset($filters['status']) && $filters['status'] == 'new') ? 'selected' : ''; ?>>Yeni</option>
                    <option value="investigating" <?php echo (isset($filters['status']) && $filters['status'] == 'investigating') ? 'selected' : ''; ?>>İncelemede</option>
                    <option value="resolved" <?php echo (isset($filters['status']) && $filters['status'] == 'resolved') ? 'selected' : ''; ?>>Çözüldü</option>
                    <option value="closed" <?php echo (isset($filters['status']) && $filters['status'] == 'closed') ? 'selected' : ''; ?>>Kapatıldı</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Öncelik</label>
                <select name="severity" class="form-select">
                    <option value="">Tümü</option>
                    <option value="critical" <?php echo (isset($filters['severity']) && $filters['severity'] == 'critical') ? 'selected' : ''; ?>>Kritik</option>
                    <option value="high" <?php echo (isset($filters['severity']) && $filters['severity'] == 'high') ? 'selected' : ''; ?>>Yüksek</option>
                    <option value="medium" <?php echo (isset($filters['severity']) && $filters['severity'] == 'medium') ? 'selected' : ''; ?>>Orta</option>
                    <option value="low" <?php echo (isset($filters['severity']) && $filters['severity'] == 'low') ? 'selected' : ''; ?>>Düşük</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo isset($filters['date_from']) ? $filters['date_from'] : ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo isset($filters['date_to']) ? $filters['date_to'] : ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Arama</label>
                <input type="text" name="search" class="form-control" placeholder="Mesaj, endpoint..." value="<?php echo isset($filters['search']) ? htmlspecialchars($filters['search']) : ''; ?>">
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrele</button>
                <a href="<?php echo base_url('admin/errors'); ?>" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Temizle</a>
            </div>
        </div>
    </form>
</div>

<!-- Hata Listesi -->
<div class="stat-card">
    <h5 class="mb-3"><i class="bi bi-bug"></i> Hata Listesi</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status Code</th>
                    <th>Error Code</th>
                    <th>Mesaj</th>
                    <th>Endpoint</th>
                    <th>Öncelik</th>
                    <th>Durum</th>
                    <th>Son Oluşma</th>
                    <th>Tekrar</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($errors)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">Hata bulunamadı</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($errors as $error): ?>
                        <tr>
                            <td><?php echo $error['id']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $error['status_code'] >= 500 ? 'danger' : 
                                        ($error['status_code'] >= 400 ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo $error['status_code']; ?>
                                </span>
                            </td>
                            <td><code><?php echo htmlspecialchars($error['error_code']); ?></code></td>
                            <td><?php echo htmlspecialchars(substr($error['message'], 0, 50)) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($error['endpoint'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $error['severity'] === 'critical' ? 'danger' : 
                                        ($error['severity'] === 'high' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($error['severity']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $error['status'] === 'new' ? 'primary' : 
                                        ($error['status'] === 'resolved' ? 'success' : 'secondary'); 
                                ?>">
                                    <?php 
                                    $status_labels = [
                                        'new' => 'Yeni',
                                        'investigating' => 'İncelemede',
                                        'resolved' => 'Çözüldü',
                                        'closed' => 'Kapatıldı',
                                        'ignored' => 'Yok Sayıldı'
                                    ];
                                    echo $status_labels[$error['status']] ?? $error['status'];
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($error['last_occurred_at'])); ?></td>
                            <td><?php echo number_format($error['occurrence_count']); ?></td>
                            <td>
                                <a href="<?php echo base_url('admin/error_detail/' . $error['id']); ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> Detay
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (isset($total_pages) && $total_pages > 1): ?>
        <nav aria-label="Sayfa navigasyonu">
            <ul class="pagination justify-content-center">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo http_build_query($filters); ?>">Önceki</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                    <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo http_build_query($filters); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo http_build_query($filters); ?>">Sonraki</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

