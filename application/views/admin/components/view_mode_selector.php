<?php
// View Mode Selector Component
$table_name = isset($table_name) ? $table_name : 'default';
$current_mode = isset($current_mode) ? $current_mode : 'standard';
?>

<div class="btn-group" role="group" aria-label="Görünüm Modu">
    <button type="button" class="btn btn-sm btn-outline-secondary view-mode-btn <?php echo $current_mode === 'minimal' ? 'active' : ''; ?>" 
            data-mode="minimal" data-table="<?php echo htmlspecialchars($table_name); ?>">
        <i class="bi bi-list"></i> Minimal
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary view-mode-btn <?php echo $current_mode === 'standard' ? 'active' : ''; ?>" 
            data-mode="standard" data-table="<?php echo htmlspecialchars($table_name); ?>">
        <i class="bi bi-list-ul"></i> Standart
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary view-mode-btn <?php echo $current_mode === 'detailed' ? 'active' : ''; ?>" 
            data-mode="detailed" data-table="<?php echo htmlspecialchars($table_name); ?>">
        <i class="bi bi-list-nested"></i> Detaylı
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary view-mode-btn <?php echo $current_mode === 'full' ? 'active' : ''; ?>" 
            data-mode="full" data-table="<?php echo htmlspecialchars($table_name); ?>">
        <i class="bi bi-list-check"></i> Tam
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewModeButtons = document.querySelectorAll('.view-mode-btn');
    
    viewModeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const mode = this.dataset.mode;
            const table = this.dataset.table;
            
            // AJAX ile görünüm modunu kaydet
            fetch('<?php echo base_url('admin/set_view_mode'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `view_mode=${mode}&table_name=${table}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aktif butonu güncelle
                    viewModeButtons.forEach(b => {
                        if (b.dataset.table === table) {
                            b.classList.remove('active');
                        }
                    });
                    this.classList.add('active');
                    
                    // Sayfayı yenile
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
</script>

