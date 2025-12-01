<?php
$analytics = isset($analytics) ? $analytics : [];
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3"><i class="bi bi-graph-up"></i> Genel İstatistikler</h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h2 class="text-primary"><?php echo number_format($analytics['total_users'] ?? 0); ?></h2>
                        <p class="text-muted mb-0">Toplam Kullanıcı</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h2 class="text-success"><?php echo number_format($analytics['total_clothing'] ?? 0); ?></h2>
                        <p class="text-muted mb-0">Toplam Kıyafet</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h2 class="text-info"><?php echo number_format($analytics['total_outfits'] ?? 0); ?></h2>
                        <p class="text-muted mb-0">Toplam Kombin</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h2 class="text-warning"><?php echo number_format($analytics['active_users'] ?? 0); ?></h2>
                        <p class="text-muted mb-0">Aktif Kullanıcı (30 gün)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="stat-card">
            <h5 class="mb-3">Kategori Dağılımı</h5>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="stat-card">
            <h5 class="mb-3">Stil Dağılımı</h5>
            <canvas id="styleChart"></canvas>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="stat-card">
            <h5 class="mb-3">Son 7 Günlük Aktivite</h5>
            <canvas id="activityChart"></canvas>
        </div>
    </div>
</div>

<script>
// Kategori dağılımı grafiği
<?php if (isset($analytics['category_distribution']) && !empty($analytics['category_distribution'])): ?>
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($analytics['category_distribution'], 'category')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($analytics['category_distribution'], 'count')); ?>,
            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe']
        }]
    }
});
<?php endif; ?>

// Stil dağılımı grafiği
<?php if (isset($analytics['style_distribution']) && !empty($analytics['style_distribution'])): ?>
const styleCtx = document.getElementById('styleChart').getContext('2d');
new Chart(styleCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($analytics['style_distribution'], 'style')); ?>,
        datasets: [{
            label: 'Kombin Sayısı',
            data: <?php echo json_encode(array_column($analytics['style_distribution'], 'count')); ?>,
            backgroundColor: '#667eea'
        }]
    }
});
<?php endif; ?>

// Aktivite grafiği
<?php if (isset($analytics['daily_activity']) && !empty($analytics['daily_activity'])): ?>
const activityCtx = document.getElementById('activityChart').getContext('2d');
const activityData = <?php echo json_encode($analytics['daily_activity']); ?>;
new Chart(activityCtx, {
    type: 'line',
    data: {
        labels: Object.keys(activityData),
        datasets: [
            {
                label: 'Yeni Kullanıcılar',
                data: Object.values(activityData).map(d => d.users),
                borderColor: '#667eea',
                tension: 0.4
            },
            {
                label: 'Yeni Kıyafetler',
                data: Object.values(activityData).map(d => d.clothing),
                borderColor: '#764ba2',
                tension: 0.4
            },
            {
                label: 'Yeni Kombinler',
                data: Object.values(activityData).map(d => d.outfits),
                borderColor: '#f093fb',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
<?php endif; ?>
</script>

