<?php
$report = isset($report) ? $report : [];
$trends = isset($trends) ? $trends : [];
$report_type = isset($report_type) ? $report_type : 'general';
$date_from = isset($date_from) ? $date_from : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($date_to) ? $date_to : date('Y-m-d');
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-graph-up-arrow"></i> Anonimleştirilmiş Raporlar</h4>
            <div>
                <a href="<?php echo base_url('admin/reports?export=1&format=json&type=' . $report_type); ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download"></i> JSON İndir
                </a>
                <a href="<?php echo base_url('admin/reports?export=1&format=csv&type=' . $report_type); ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV İndir
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtreler -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <form method="get" action="<?php echo base_url('admin/reports'); ?>">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Rapor Tipi</label>
                        <select name="type" class="form-select">
                            <option value="general" <?php echo $report_type === 'general' ? 'selected' : ''; ?>>Genel</option>
                            <option value="usage" <?php echo $report_type === 'usage' ? 'selected' : ''; ?>>Kullanım</option>
                            <option value="engagement" <?php echo $report_type === 'engagement' ? 'selected' : ''; ?>>Etkileşim</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tarih Başlangıç</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tarih Bitiş</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Metrik</label>
                        <select name="metric" class="form-select">
                            <option value="users" <?php echo (isset($_GET['metric']) && $_GET['metric'] === 'users') ? 'selected' : ''; ?>>Kullanıcılar</option>
                            <option value="clothing" <?php echo (isset($_GET['metric']) && $_GET['metric'] === 'clothing') ? 'selected' : ''; ?>>Kıyafetler</option>
                            <option value="outfits" <?php echo (isset($_GET['metric']) && $_GET['metric'] === 'outfits') ? 'selected' : ''; ?>>Kombinler</option>
                            <option value="activity" <?php echo (isset($_GET['metric']) && $_GET['metric'] === 'activity') ? 'selected' : ''; ?>>Aktivite</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Periyot</label>
                        <select name="period" class="form-select">
                            <option value="daily" <?php echo (isset($_GET['period']) && $_GET['period'] === 'daily') ? 'selected' : ''; ?>>Günlük</option>
                            <option value="weekly" <?php echo (isset($_GET['period']) && $_GET['period'] === 'weekly') ? 'selected' : ''; ?>>Haftalık</option>
                            <option value="monthly" <?php echo (isset($_GET['period']) && $_GET['period'] === 'monthly') ? 'selected' : ''; ?>>Aylık</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrele
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Trend Grafiği -->
<?php if (!empty($trends['trends'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">Trend Analizi: <?php echo ucfirst($trends['metric']); ?> (<?php echo ucfirst($trends['period']); ?>)</h5>
            <canvas id="trendChart" height="80"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Genel Rapor -->
<?php if ($report_type === 'general'): ?>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card text-center">
            <h2 class="text-primary"><?php echo number_format($report['total_users'] ?? 0); ?></h2>
            <p class="text-muted mb-0">Toplam Kullanıcı</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <h2 class="text-success"><?php echo number_format($report['total_clothing'] ?? 0); ?></h2>
            <p class="text-muted mb-0">Toplam Kıyafet</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <h2 class="text-info"><?php echo number_format($report['total_outfits'] ?? 0); ?></h2>
            <p class="text-muted mb-0">Toplam Kombin</p>
        </div>
    </div>
</div>

<?php if (!empty($report['category_totals'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">Kategori Dağılımı</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Toplam</th>
                            <th>Benzersiz Kullanıcı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['category_totals'] as $cat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat['category'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($cat['total'] ?? 0); ?></td>
                            <td><?php echo number_format($cat['unique_users'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($report['style_totals'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">Stil Dağılımı</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Stil</th>
                            <th>Toplam</th>
                            <th>Benzersiz Kullanıcı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['style_totals'] as $style): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($style['style'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($style['total'] ?? 0); ?></td>
                            <td><?php echo number_format($style['unique_users'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Kullanım Raporu -->
<?php if ($report_type === 'usage'): ?>
<?php if (!empty($report['daily_active_users'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">Günlük Aktif Kullanıcılar</h5>
            <canvas id="dailyActiveChart" height="80"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="stat-card text-center">
            <h2 class="text-primary"><?php echo number_format($report['avg_clothing_per_user'] ?? 0, 2); ?></h2>
            <p class="text-muted mb-0">Ortalama Kıyafet/Kullanıcı</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card text-center">
            <h2 class="text-success"><?php echo number_format($report['avg_outfits_per_user'] ?? 0, 2); ?></h2>
            <p class="text-muted mb-0">Ortalama Kombin/Kullanıcı</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Etkileşim Raporu -->
<?php if ($report_type === 'engagement'): ?>
<?php if (!empty($report['daily_outfit_usage'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">Günlük Kombin Kullanımı</h5>
            <canvas id="outfitUsageChart" height="80"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($report['popular_categories'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">En Popüler Kategoriler</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Kullanım Sayısı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['popular_categories'] as $cat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat['category'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($cat['usage_count'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
<?php if (!empty($trends['trends'])): ?>
// Trend Chart
const trendCtx = document.getElementById('trendChart');
if (trendCtx) {
    const trendData = <?php echo json_encode($trends['trends']); ?>;
    const labels = trendData.map(item => item.date || item.week || item.month);
    const counts = trendData.map(item => parseInt(item.count));
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '<?php echo ucfirst($trends['metric']); ?>',
                data: counts,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
<?php endif; ?>

<?php if ($report_type === 'usage' && !empty($report['daily_active_users'])): ?>
// Daily Active Users Chart
const dailyActiveCtx = document.getElementById('dailyActiveChart');
if (dailyActiveCtx) {
    const dailyData = <?php echo json_encode($report['daily_active_users']); ?>;
    const labels = dailyData.map(item => item.date);
    const counts = dailyData.map(item => parseInt(item.daily_active_users));
    
    new Chart(dailyActiveCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Günlük Aktif Kullanıcılar',
                data: counts,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
<?php endif; ?>

<?php if ($report_type === 'engagement' && !empty($report['daily_outfit_usage'])): ?>
// Outfit Usage Chart
const outfitUsageCtx = document.getElementById('outfitUsageChart');
if (outfitUsageCtx) {
    const usageData = <?php echo json_encode($report['daily_outfit_usage']); ?>;
    const labels = usageData.map(item => item.date);
    const counts = usageData.map(item => parseInt(item.count));
    
    new Chart(outfitUsageCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Günlük Kombin Kullanımı',
                data: counts,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
<?php endif; ?>
</script>

