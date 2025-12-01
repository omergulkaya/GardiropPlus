<?php
/**
 * API Error Summary Widget
 * Dashboard için hata özeti widget'ı
 */
$errors = isset($errors) ? $errors : [];
$last_24h = isset($last_24h) ? $last_24h : 0;
$critical = isset($critical) ? $critical : 0;
$top_endpoints = isset($top_endpoints) ? $top_endpoints : [];
$trend = isset($trend) ? $trend : [];
?>

<div class="stat-card" data-widget="error-summary" data-widget-id="error-summary">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-bug text-danger"></i> API Hata Özeti
        </h5>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" onclick="refreshErrorWidget()" title="Yenile">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button class="btn btn-outline-secondary" onclick="exportErrorWidget()" title="Dışa Aktar">
                <i class="bi bi-download"></i>
            </button>
        </div>
    </div>
    
    <!-- Summary Stats -->
    <div class="row mb-3">
        <div class="col-6">
            <div class="text-center p-3 bg-light rounded">
                <h3 class="text-danger mb-0"><?php echo number_format($last_24h); ?></h3>
                <small class="text-muted">Son 24 Saat</small>
            </div>
        </div>
        <div class="col-6">
            <div class="text-center p-3 bg-light rounded">
                <h3 class="text-warning mb-0"><?php echo number_format($critical); ?></h3>
                <small class="text-muted">Kritik Hatalar</small>
            </div>
        </div>
    </div>
    
    <!-- Top Endpoints -->
    <?php if (!empty($top_endpoints)): ?>
    <div class="mb-3">
        <h6 class="text-muted mb-2">En Çok Hata Veren Endpoint'ler</h6>
        <ul class="list-unstyled mb-0">
            <?php foreach (array_slice($top_endpoints, 0, 5) as $endpoint): ?>
            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <code class="small"><?php echo htmlspecialchars(substr($endpoint['endpoint'] ?? 'N/A', 0, 40)); ?></code>
                <span class="badge bg-danger"><?php echo number_format($endpoint['error_count'] ?? 0); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Trend Chart -->
    <?php if (!empty($trend)): ?>
    <div>
        <h6 class="text-muted mb-2">Hata Trendi (Son 7 Gün)</h6>
        <canvas id="errorTrendChart" height="60"></canvas>
    </div>
    <?php endif; ?>
</div>

<script>
function refreshErrorWidget() {
    // Real-time update için AJAX çağrısı
    fetch('/admin/api/error_widget_data')
        .then(response => response.json())
        .then(data => {
            // Widget'ı güncelle
            console.log('Error widget refreshed', data);
            if (window.Toast) {
                window.Toast.success('Hata özeti güncellendi');
            }
        })
        .catch(error => {
            console.error('Error refreshing widget:', error);
            if (window.Toast) {
                window.Toast.error('Güncelleme başarısız');
            }
        });
}

function exportErrorWidget() {
    // Export functionality
    window.location.href = '/admin/error_statistics?export=pdf';
}

<?php if (!empty($trend)): ?>
// Error Trend Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('errorTrendChart');
    if (ctx && typeof Chart !== 'undefined') {
        const trendData = <?php echo json_encode($trend); ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.map(item => item.date || item.day),
                datasets: [{
                    label: 'Hata Sayısı',
                    data: trendData.map(item => parseInt(item.count || 0)),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
<?php endif; ?>
</script>

