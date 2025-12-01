<div class="stat-card">
    <h5 class="mb-3"><i class="bi bi-gear"></i> Sistem Ayarları</h5>
    
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $this->session->flashdata('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo base_url('admin/settings'); ?>">
        <div class="mb-3">
            <label class="form-label">Site Adı</label>
            <input type="text" class="form-control" name="site_name" value="GardıropPlus" readonly>
        </div>
        
        <div class="mb-3">
            <label class="form-label">API Versiyonu</label>
            <input type="text" class="form-control" value="1.0.0" readonly>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Veritabanı Durumu</label>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> Veritabanı bağlantısı aktif
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Sistem Bilgileri</label>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between">
                    <span>PHP Versiyonu</span>
                    <strong><?php echo PHP_VERSION; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>CodeIgniter Versiyonu</span>
                    <strong><?php echo CI_VERSION; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Sunucu Zamanı</span>
                    <strong><?php echo date('d.m.Y H:i:s'); ?></strong>
                </li>
            </ul>
        </div>
        
        <button type="submit" class="btn btn-primary" disabled>
            <i class="bi bi-save"></i> Ayarları Kaydet
        </button>
        <small class="d-block text-muted mt-2">Ayarlar yakında eklenecektir.</small>
    </form>
</div>

