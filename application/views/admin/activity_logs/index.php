<?php
$this->load->helper('privacy');
$logs = isset($logs) ? $logs : [];
$filters = isset($filters) ? $filters : [];
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3"><i class="bi bi-clock-history"></i> Admin Aktivite Logları</h5>
            <p class="text-muted">Tüm admin aktiviteleri burada loglanmaktadır. Bu loglar GDPR/KVKK uyumluluğu için gereklidir.</p>
        </div>
    </div>
</div>

<!-- Filtreler -->
<div class="stat-card mb-4">
    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filtreler</h5>
    <form method="get" action="<?php echo base_url('admin/activity_logs'); ?>">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Admin</label>
                <input type="number" name="admin_id" class="form-control" placeholder="Admin ID" value="<?php echo isset($filters['admin_id']) ? htmlspecialchars($filters['admin_id']) : ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">İşlem</label>
                <select name="action" class="form-select">
                    <option value="">Tümü</option>
                    <option value="view_user" <?php echo (isset($filters['action']) && $filters['action'] == 'view_user') ? 'selected' : ''; ?>>Kullanıcı Görüntüleme</option>
                    <option value="edit_user" <?php echo (isset($filters['action']) && $filters['action'] == 'edit_user') ? 'selected' : ''; ?>>Kullanıcı Düzenleme</option>
                    <option value="delete_user" <?php echo (isset($filters['action']) && $filters['action'] == 'delete_user') ? 'selected' : ''; ?>>Kullanıcı Silme</option>
                    <option value="view_clothing" <?php echo (isset($filters['action']) && $filters['action'] == 'view_clothing') ? 'selected' : ''; ?>>Kıyafet Görüntüleme</option>
                    <option value="view_outfit" <?php echo (isset($filters['action']) && $filters['action'] == 'view_outfit') ? 'selected' : ''; ?>>Kombin Görüntüleme</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Kaynak Tipi</label>
                <select name="resource_type" class="form-select">
                    <option value="">Tümü</option>
                    <option value="user" <?php echo (isset($filters['resource_type']) && $filters['resource_type'] == 'user') ? 'selected' : ''; ?>>Kullanıcı</option>
                    <option value="clothing_item" <?php echo (isset($filters['resource_type']) && $filters['resource_type'] == 'clothing_item') ? 'selected' : ''; ?>>Kıyafet</option>
                    <option value="outfit" <?php echo (isset($filters['resource_type']) && $filters['resource_type'] == 'outfit') ? 'selected' : ''; ?>>Kombin</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Şüpheli Aktivite</label>
                <select name="is_suspicious" class="form-select">
                    <option value="">Tümü</option>
                    <option value="1" <?php echo (isset($filters['is_suspicious']) && $filters['is_suspicious'] == '1') ? 'selected' : ''; ?>>Sadece Şüpheli</option>
                    <option value="0" <?php echo (isset($filters['is_suspicious']) && $filters['is_suspicious'] == '0') ? 'selected' : ''; ?>>Normal</option>
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
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrele</button>
                <a href="<?php echo base_url('admin/activity_logs'); ?>" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Temizle</a>
            </div>
        </div>
    </form>
</div>

<!-- Aktivite Logları Listesi -->
<div class="stat-card">
    <h5 class="mb-3"><i class="bi bi-list-ul"></i> Aktivite Listesi</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Admin</th>
                    <th>İşlem</th>
                    <th>Kaynak</th>
                    <th>IP Adresi</th>
                    <th>Şüpheli</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Aktivite logu bulunamadı</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="<?php echo $log['is_suspicious'] ? 'table-warning' : ''; ?>">
                            <td><?php echo $log['id']; ?></td>
                            <td>
                                <?php 
                                // Admin bilgisini göster
                                if (isset($log['admin_info']) && $log['admin_info']) {
                                    $admin = $log['admin_info'];
                                    echo htmlspecialchars(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
                                    echo '<br><small class="text-muted">ID: ' . $log['admin_id'] . '</small>';
                                } else {
                                    echo 'ID: ' . $log['admin_id'];
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['resource_type']): ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($log['resource_type']); ?></span>
                                    <?php if ($log['resource_id']): ?>
                                        <br><small class="text-muted">ID: <?php echo htmlspecialchars($log['resource_id']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></code>
                            </td>
                            <td>
                                <?php if ($log['is_suspicious']): ?>
                                    <span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Şüpheli</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td>
                                <?php if ($log['details']): ?>
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $log['id']; ?>">
                                        <i class="bi bi-info-circle"></i> Detay
                                    </button>
                                    
                                    <!-- Details Modal -->
                                    <div class="modal fade" id="detailsModal<?php echo $log['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Aktivite Detayları</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <h6>İşlem Bilgileri</h6>
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <th>İşlem:</th>
                                                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Kaynak Tipi:</th>
                                                            <td><?php echo htmlspecialchars($log['resource_type'] ?? '-'); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Kaynak ID:</th>
                                                            <td><?php echo htmlspecialchars($log['resource_id'] ?? '-'); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>IP Adresi:</th>
                                                            <td><code><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></code></td>
                                                        </tr>
                                                        <tr>
                                                            <th>User Agent:</th>
                                                            <td><code><?php echo htmlspecialchars($log['user_agent'] ?? '-'); ?></code></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Tarih:</th>
                                                            <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                                                        </tr>
                                                    </table>
                                                    
                                                    <?php if ($log['details']): ?>
                                                        <h6 class="mt-3">Detaylar (JSON)</h6>
                                                        <pre class="bg-light p-3 rounded"><code><?php echo htmlspecialchars(json_encode($log['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></code></pre>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
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

