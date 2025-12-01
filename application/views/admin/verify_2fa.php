<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : '2FA Doğrulama'; ?> - GardıropPlus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock fs-1 text-primary"></i>
                            <h3 class="mt-3">2FA Doğrulama</h3>
                            <p class="text-muted">Güvenliğiniz için iki faktörlü doğrulama gereklidir</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo base_url('admin/verify_2fa'); ?>">
                            <div class="mb-3">
                                <label for="code" class="form-label">Doğrulama Kodu</label>
                                <input type="text" 
                                       class="form-control form-control-lg text-center" 
                                       id="code" 
                                       name="code" 
                                       placeholder="000000" 
                                       maxlength="6" 
                                       pattern="[0-9]{6}" 
                                       required 
                                       autofocus>
                                <small class="form-text text-muted">
                                    Google Authenticator uygulamanızdan 6 haneli kodu girin
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-check-circle"></i> Doğrula
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="<?php echo base_url('admin/login'); ?>" class="text-muted">
                                <i class="bi bi-arrow-left"></i> Geri Dön
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

