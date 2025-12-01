<?php
$policies = isset($policies) ? $policies : [];
$cleanup_logs = isset($cleanup_logs) ? $cleanup_logs : [];
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3"><i class="bi bi-database-check"></i> Veri Saklama Politikaları</h5>
            <p class="text-muted">Veri saklama sürelerini ve otomatik temizleme ayarlarını yönetin.</p>
        </div>
    </div>
</div>

<!-- Politikalar -->
<div class="stat-card mb-4">
    <h5 class="mb-3">Politikalar</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Veri Tipi</th>
                    <th>Saklama Süresi (Gün)</th>
                    <th>Otomatik Silme</th>
                    <th>Son Temizleme</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($policies as $policy): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($policy['data_type']); ?></strong></td>
                        <td>
                            <form method="post" action="<?php echo base_url('admin/data_retention'); ?>" class="d-inline">
                                <input type="hidden" name="update_policy" value="1">
                                <input type="hidden" name="data_type" value="<?php echo htmlspecialchars($policy['data_type']); ?>">
                                <div class="input-group" style="width: 150px;">
                                    <input type="number" 
                                           name="retention_days" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo $policy['retention_days']; ?>" 
                                           min="1" 
                                           required>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-check"></i>
                                    </button>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="<?php echo base_url('admin/data_retention'); ?>" class="d-inline">
                                <input type="hidden" name="update_policy" value="1">
                                <input type="hidden" name="data_type" value="<?php echo htmlspecialchars($policy['data_type']); ?>">
                                <input type="hidden" name="retention_days" value="<?php echo $policy['retention_days']; ?>">
                                <div class="form-check form-switch">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           name="auto_delete" 
                                           value="1" 
                                           <?php echo $policy['auto_delete'] ? 'checked' : ''; ?>
                                           onchange="this.form.submit()">
                                </div>
                            </form>
                        </td>
                        <td>
                            <?php if ($policy['last_cleanup_at']): ?>
                                <?php echo date('d.m.Y H:i', strtotime($policy['last_cleanup_at'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Henüz çalışmadı</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="<?php echo base_url('admin/data_retention'); ?>" class="d-inline">
                                <input type="hidden" name="run_cleanup" value="1">
                                <input type="hidden" name="data_type" value="<?php echo htmlspecialchars($policy['data_type']); ?>">
                                <button type="submit" 
                                        class="btn btn-sm btn-warning" 
                                        onclick="return confirm('Bu veri tipi için temizleme işlemini şimdi çalıştırmak istediğinize emin misiniz?');">
                                    <i class="bi bi-trash"></i> Şimdi Temizle
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Temizleme Logları -->
<div class="stat-card">
    <h5 class="mb-3"><i class="bi bi-clock-history"></i> Temizleme Logları</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Veri Tipi</th>
                    <th>Silinen Kayıt</th>
                    <th>Süre (sn)</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cleanup_logs)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Henüz temizleme logu yok</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cleanup_logs as $log): ?>
                        <tr>
                            <td><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['data_type']); ?></td>
                            <td><?php echo number_format($log['records_deleted']); ?></td>
                            <td><?php echo number_format($log['execution_time'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $log['status'] === 'success' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($log['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

