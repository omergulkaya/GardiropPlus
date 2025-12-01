<?php
$deletion_requests = isset($deletion_requests) ? $deletion_requests : [];
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3"><i class="bi bi-eye-slash"></i> Gizlilik Ayarları ve Veri Silme Talepleri</h5>
            <p class="text-muted">Kullanıcıların veri silme taleplerini yönetin.</p>
        </div>
    </div>
</div>

<!-- Veri Silme Talepleri -->
<div class="stat-card">
    <h5 class="mb-3"><i class="bi bi-trash"></i> Bekleyen Veri Silme Talepleri</h5>
    
    <?php if (empty($deletion_requests)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Bekleyen veri silme talebi yok.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kullanıcı ID</th>
                        <th>Talep Tarihi</th>
                        <th>Neden</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deletion_requests as $request): ?>
                        <tr>
                            <td><?php echo $request['user_id']; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($request['requested_at'])); ?></td>
                            <td><?php echo htmlspecialchars($request['request_reason'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-warning">Beklemede</span>
                            </td>
                            <td>
                                <form method="post" action="<?php echo base_url('admin/privacy_settings'); ?>" class="d-inline">
                                    <input type="hidden" name="process_deletion" value="1">
                                    <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Bu kullanıcının verilerini silmek istediğinize emin misiniz? Bu işlem geri alınamaz!');">
                                        <i class="bi bi-check-circle"></i> Onayla ve Sil
                                    </button>
                                </form>
                                
                                <button type="button" 
                                        class="btn btn-sm btn-secondary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#rejectModal<?php echo $request['id']; ?>">
                                    <i class="bi bi-x-circle"></i> Reddet
                                </button>
                                
                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?php echo $request['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Silme Talebini Reddet</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post" action="<?php echo base_url('admin/privacy_settings'); ?>">
                                                <div class="modal-body">
                                                    <input type="hidden" name="process_deletion" value="1">
                                                    <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Red Nedeni</label>
                                                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                                        <small class="form-text text-muted">Kullanıcıya gösterilecek red nedeni</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                    <button type="submit" class="btn btn-danger">Reddet</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="stat-card mt-4">
    <h5 class="mb-3"><i class="bi bi-info-circle"></i> Bilgi</h5>
    <ul>
        <li>Veri silme talepleri GDPR/KVKK uyumluluğu için gereklidir</li>
        <li>Onaylanan taleplerde kullanıcı verileri anonimleştirilerek silinir</li>
        <li>Yasal zorunluluklar nedeniyle bazı loglar korunabilir</li>
        <li>Silme işlemi geri alınamaz</li>
    </ul>
</div>

