<div class="stat-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Kombinler</h5>
        <span class="badge bg-primary">Toplam: <?php echo number_format($total_outfits ?? 0); ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>İsim</th>
                    <th>Kullanıcı ID</th>
                    <th>Stil</th>
                    <th>Kıyafet Sayısı</th>
                    <th>Favori</th>
                    <th>Oluşturma Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($outfits)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Henüz kombin yok</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($outfits as $outfit): ?>
                        <tr>
                            <td><?php echo $outfit['id']; ?></td>
                            <td><?php echo htmlspecialchars($outfit['name'] ?? 'İsimsiz'); ?></td>
                            <td><?php echo $outfit['user_id'] ?? '-'; ?></td>
                            <td><?php echo htmlspecialchars($outfit['style'] ?? '-'); ?></td>
                            <td><?php echo isset($outfit['items']) ? count($outfit['items']) : 0; ?></td>
                            <td>
                                <?php if (isset($outfit['is_favorite']) && $outfit['is_favorite']): ?>
                                    <span class="badge bg-warning"><i class="bi bi-star-fill"></i></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo isset($outfit['created_at']) ? date('d.m.Y H:i', strtotime($outfit['created_at'])) : '-'; ?></td>
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

