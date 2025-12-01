<?php if (!$user): ?>
    <div class="alert alert-danger">Kullanıcı bulunamadı.</div>
<?php else: ?>
    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <h5 class="mb-3">Kullanıcı Bilgileri</h5>
                <p><strong>Ad:</strong> <?php echo htmlspecialchars($user['first_name'] ?? ''); ?></p>
                <p><strong>Soyad:</strong> <?php echo htmlspecialchars($user['last_name'] ?? ''); ?></p>
                <p><strong>E-posta:</strong> <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                <p><strong>Telefon:</strong> <?php echo htmlspecialchars($user['phone'] ?? '-'); ?></p>
                <p><strong>Kayıt Tarihi:</strong> <?php echo isset($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '-'; ?></p>
                <p><strong>Son Giriş:</strong> <?php echo isset($user['last_login']) ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Hiç giriş yapmamış'; ?></p>
                <p><strong>E-posta Doğrulandı:</strong> 
                    <?php if (isset($user['email_verified']) && $user['email_verified']): ?>
                        <span class="badge bg-success">Evet</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Hayır</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="stat-card mb-3">
                <h5 class="mb-3">Kıyafetler (<?php echo count($user_clothing ?? []); ?>)</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>İsim</th>
                                <th>Kategori</th>
                                <th>Eklenme Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user_clothing)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Kıyafet yok</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($user_clothing, 0, 10) as $item): ?>
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
            </div>
            
            <div class="stat-card">
                <h5 class="mb-3">Kombinler (<?php echo count($user_outfits ?? []); ?>)</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>İsim</th>
                                <th>Stil</th>
                                <th>Oluşturma Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user_outfits)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Kombin yok</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($user_outfits, 0, 10) as $outfit): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($outfit['name'] ?? 'İsimsiz'); ?></td>
                                        <td><?php echo htmlspecialchars($outfit['style'] ?? '-'); ?></td>
                                        <td><?php echo isset($outfit['created_at']) ? date('d.m.Y', strtotime($outfit['created_at'])) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="<?php echo base_url('admin/users'); ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri Dön
        </a>
    </div>
<?php endif; ?>

