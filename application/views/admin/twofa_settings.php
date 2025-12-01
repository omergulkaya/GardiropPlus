<?php
$twofa = isset($twofa) ? $twofa : [];
$show_setup = isset($show_setup) ? $show_setup : false;
?>

<div class="stat-card">
    <h5 class="mb-3"><i class="bi bi-shield-lock"></i> İki Faktörlü Doğrulama (2FA) Ayarları</h5>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($twofa['is_enabled']): ?>
        <div class="alert alert-success">
            <i class="bi bi-shield-check"></i> 2FA aktif
            <br><small>Yöntem: <?php echo strtoupper($twofa['method']); ?></small>
        </div>
        
        <form method="post" action="<?php echo base_url('admin/twofa_settings'); ?>">
            <input type="hidden" name="action" value="disable">
            <button type="submit" class="btn btn-danger" onclick="return confirm('2FA\'yı devre dışı bırakmak istediğinize emin misiniz?');">
                <i class="bi bi-x-circle"></i> 2FA'yı Devre Dışı Bırak
            </button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-shield-exclamation"></i> 2FA devre dışı
        </div>

        <?php if ($show_setup && isset($secret)): ?>
            <!-- TOTP Setup -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6>1. QR Kodu Tarayın</h6>
                    <p>Google Authenticator veya benzeri bir uygulama ile QR kodu tarayın:</p>
                    <div class="text-center mb-3">
                        <img src="<?php echo htmlspecialchars($qr_url); ?>" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                    </div>
                    
                    <h6>2. Secret Key (Manuel Giriş İçin)</h6>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($secret); ?>" readonly id="secretKey">
                        <button class="btn btn-outline-secondary" type="button" onclick="copySecret()">
                            <i class="bi bi-clipboard"></i> Kopyala
                        </button>
                    </div>
                    
                    <h6>3. Yedek Kodlar</h6>
                    <p>Bu kodları güvenli bir yerde saklayın. Her kod sadece bir kez kullanılabilir:</p>
                    <div class="alert alert-info">
                        <?php foreach ($backup_codes as $code): ?>
                            <code><?php echo htmlspecialchars($code); ?></code><br>
                        <?php endforeach; ?>
                    </div>
                    
                    <h6>4. Doğrulama Kodu Girin</h6>
                    <form method="post" action="<?php echo base_url('admin/twofa_settings'); ?>">
                        <input type="hidden" name="action" value="verify_totp">
                        <div class="mb-3">
                            <input type="text" 
                                   class="form-control text-center" 
                                   name="code" 
                                   placeholder="000000" 
                                   maxlength="6" 
                                   pattern="[0-9]{6}" 
                                   required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Doğrula ve Etkinleştir
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif (isset($pending_email_verification) && $pending_email_verification): ?>
            <!-- Email 2FA Setup -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6>E-posta Doğrulama Kodu</h6>
                    <p>Doğrulama kodu <strong><?php echo htmlspecialchars($admin['email'] ?? ''); ?></strong> adresine gönderildi.</p>
                    <p>E-posta kutunuzu kontrol edin ve 6 haneli kodu girin.</p>
                    
                    <form method="post" action="<?php echo base_url('admin/twofa_settings'); ?>">
                        <input type="hidden" name="action" value="verify_email">
                        <div class="mb-3">
                            <input type="text" 
                                   class="form-control text-center" 
                                   name="code" 
                                   placeholder="000000" 
                                   maxlength="6" 
                                   pattern="[0-9]{6}" 
                                   required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Doğrula ve Etkinleştir
                        </button>
                    </form>
                    
                    <form method="post" action="<?php echo base_url('admin/twofa_settings'); ?>" class="mt-2">
                        <input type="hidden" name="action" value="enable_email">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Kodu Yeniden Gönder
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="d-grid gap-2">
                <form method="post" action="<?php echo base_url('admin/twofa_settings'); ?>">
                    <input type="hidden" name="action" value="enable_totp">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-shield-lock"></i> TOTP (Google Authenticator) Etkinleştir
                    </button>
                </form>
                
                <form method="post" action="<?php echo base_url('admin/twofa_settings'); ?>">
                    <input type="hidden" name="action" value="enable_email">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-envelope"></i> E-posta ile 2FA Etkinleştir
                    </button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function copySecret() {
    var secretInput = document.getElementById('secretKey');
    secretInput.select();
    document.execCommand('copy');
    alert('Secret key kopyalandı!');
}
</script>

