<?php
$stats = isset($stats) ? $stats : [];
$period = isset($period) ? $period : 'daily';
$days = isset($days) ? $days : 30;
$date_from = isset($date_from) ? $date_from : date('Y-m-d', strtotime("-{$days} days"));
$date_to = isset($date_to) ? $date_to : date('Y-m-d');
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-graph-up-arrow"></i> Hata İstatistikleri</h4>
        </div>
    </div>
</div>

<!-- Filtreler -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <form method="get" action="<?php echo base_url('admin/error_statistics'); ?>">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Periyot</label>
                        <select name="period" class="form-select">
                            <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                            <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                            <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Gün Sayısı</label>
                        <input type="number" name="days" class="form-control" value="<?php echo $days; ?>" min="1" max="365">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tarih Başlangıç</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tarih Bitiş</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-3">
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

<!-- Özet İstatistikler -->
<?php if (!empty($stats['summary'])): ?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <h2 class="text-primary"><?php echo number_format($stats['summary']['total_errors'] ?? 0); ?></h2>
            <p class="text-muted mb-0">Toplam Hata (<?php echo $days; ?> gün)</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <h2 class="text-danger"><?php echo number_format($stats['summary']['critical_errors'] ?? 0); ?></h2>
            <p class="text-muted mb-0">Kritik Hatalar</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <h2 class="text-warning"><?php echo number_format($stats['summary']['unresolved_errors'] ?? 0); ?></h2>
            <p class="text-muted mb-0">Çözülmemiş</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <h2 class="text-info"><?php echo number_format($stats['summary']['last_24h'] ?? 0); ?></h2>
            <p class="text-muted mb-0">Son 24 Saat</p>
        </div>
    </div>
</div>

<?php if (!empty($stats['summary']['avg_resolution_hours'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card text-center">
            <h3 class="text-success"><?php echo number_format($stats['summary']['avg_resolution_hours'], 2); ?> saat</h3>
            <p class="text-muted mb-0">Ortalama Çözülme Süresi</p>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Hata Trend Grafiği -->
<?php if (!empty($stats['trend'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">Hata Sayısı Trend Grafiği (<?php echo ucfirst($period); ?>)</h5>
            <canvas id="trendChart" height="80"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hata Tipi Dağılımı -->
<?php if (!empty($stats['type_distribution'])): ?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <h5 class="mb-3">Status Code Dağılımı</h5>
            <canvas id="statusCodeChart" height="80"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <h5 class="mb-3">Severity Dağılımı</h5>
            <canvas id="severityChart" height="80"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- En Çok Hata Veren Endpoint'ler -->
<?php if (!empty($stats['top_endpoints'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">En Çok Hata Veren Endpoint'ler</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Hata Sayısı</th>
                            <th>Benzersiz Hata Kodu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_endpoints'] as $endpoint): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($endpoint['endpoint'] ?? 'N/A'); ?></code></td>
                            <td><span class="badge bg-danger"><?php echo number_format($endpoint['error_count'] ?? 0); ?></span></td>
                            <td><?php echo number_format($endpoint['unique_errors'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hata Çözülme Süreleri -->
<?php if (!empty($stats['resolution_times'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">Hata Çözülme Süreleri</h5>
            <?php if (!empty($stats['resolution_times']['overall'])): ?>
            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Ortalama:</strong> <?php echo number_format($stats['resolution_times']['overall']['avg_hours'] ?? 0, 2); ?> saat
                </div>
                <div class="col-md-3">
                    <strong>En Hızlı:</strong> <?php echo number_format($stats['resolution_times']['overall']['min_minutes'] ?? 0, 2); ?> dakika
                </div>
                <div class="col-md-3">
                    <strong>En Yavaş:</strong> <?php echo number_format($stats['resolution_times']['overall']['max_minutes'] ?? 0, 2); ?> dakika
                </div>
                <div class="col-md-3">
                    <strong>Çözülen Hata:</strong> <?php echo number_format($stats['resolution_times']['overall']['resolved_count'] ?? 0); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($stats['resolution_times']['by_severity'])): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Severity</th>
                            <th>Ortalama Çözülme Süresi (Saat)</th>
                            <th>Çözülen Hata Sayısı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['resolution_times']['by_severity'] as $item): ?>
                        <tr>
                            <td><span class="badge bg-<?php echo $item['severity'] === 'critical' ? 'danger' : ($item['severity'] === 'high' ? 'warning' : 'info'); ?>"><?php echo strtoupper($item['severity'] ?? 'N/A'); ?></span></td>
                            <td><?php echo number_format($item['avg_hours'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($item['count'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
<?php if (!empty($stats['trend'])): ?>
// Trend Chart
const trendCtx = document.getElementById('trendChart');
if (trendCtx) {
    const trendData = <?php echo json_encode($stats['trend']); ?>;
    const labels = trendData.map(item => item.date || item.week || item.month);
    const counts = trendData.map(item => parseInt(item.count));
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Hata Sayısı',
                data: counts,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1,
                fill: true
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

<?php if (!empty($stats['type_distribution']['by_status_code'])): ?>
// Status Code Chart
const statusCodeCtx = document.getElementById('statusCodeChart');
if (statusCodeCtx) {
    const statusData = <?php echo json_encode($stats['type_distribution']['by_status_code']); ?>;
    const labels = statusData.map(item => item.status_code);
    const counts = statusData.map(item => parseInt(item.count));
    
    new Chart(statusCodeCtx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true
        }
    });
}
<?php endif; ?>

<?php if (!empty($stats['type_distribution']['by_severity'])): ?>
// Severity Chart
const severityCtx = document.getElementById('severityChart');
if (severityCtx) {
    const severityData = <?php echo json_encode($stats['type_distribution']['by_severity']); ?>;
    const labels = severityData.map(item => item.severity);
    const counts = severityData.map(item => parseInt(item.count));
    
    new Chart(severityCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Hata Sayısı',
                data: counts,
                backgroundColor: [
                    'rgba(220, 53, 69, 0.8)',  // critical - red
                    'rgba(255, 193, 7, 0.8)',  // high - yellow
                    'rgba(0, 123, 255, 0.8)',  // medium - blue
                    'rgba(40, 167, 69, 0.8)'   // low - green
                ]
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

