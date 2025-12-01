<div class="stat-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-people"></i> Kullanıcılar</h5>
        <span class="badge bg-primary">Toplam: <?php echo number_format($total_users ?? 0); ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Kayıt Tarihi</th>
                    <th>Son Giriş</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Henüz kullanıcı yok</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                            <td><?php echo isset($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '-'; ?></td>
                            <td><?php echo isset($user['last_login']) ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Hiç giriş yapmamış'; ?></td>
                            <td>
                                <a href="<?php echo base_url('admin/users/' . $user['id']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Detay
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (isset($total_pages) && $total_pages > 1): ?>
        <nav aria-label="Sayfa navigasyonu">
            <ul class="pagination justify-content-center">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?>">Önceki</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?>">Sonraki</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

