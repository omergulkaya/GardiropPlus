<?php
// Column Visibility Selector Component
$table_name = isset($table_name) ? $table_name : 'default';
$all_columns = isset($all_columns) ? $all_columns : [];
$visible_columns = isset($visible_columns) ? $visible_columns : [];
?>

<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="columnVisibilityDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-columns-gap"></i> Sütun Görünürlüğü
    </button>
    <ul class="dropdown-menu" aria-labelledby="columnVisibilityDropdown" style="max-height: 400px; overflow-y: auto;">
        <?php foreach ($all_columns as $column): ?>
        <li>
            <div class="form-check ms-2">
                <input class="form-check-input column-visibility-checkbox" 
                       type="checkbox" 
                       value="<?php echo htmlspecialchars($column); ?>" 
                       id="col_<?php echo htmlspecialchars($table_name . '_' . $column); ?>"
                       data-table="<?php echo htmlspecialchars($table_name); ?>"
                       <?php echo in_array($column, $visible_columns) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="col_<?php echo htmlspecialchars($table_name . '_' . $column); ?>">
                    <?php echo htmlspecialchars($column); ?>
                </label>
            </div>
        </li>
        <?php endforeach; ?>
        <li><hr class="dropdown-divider"></li>
        <li>
            <button class="dropdown-item" type="button" onclick="saveColumnPreferences('<?php echo htmlspecialchars($table_name); ?>')">
                <i class="bi bi-check-circle"></i> Kaydet
            </button>
        </li>
    </ul>
</div>

<script>
function saveColumnPreferences(tableName) {
    const checkboxes = document.querySelectorAll(`.column-visibility-checkbox[data-table="${tableName}"]`);
    const visibleColumns = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
    
    fetch('<?php echo base_url('admin/save_column_preferences'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `table_name=${tableName}&visible_columns=${JSON.stringify(visibleColumns)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sütun tercihleri kaydedildi!');
            location.reload();
        } else {
            alert('Hata: ' + (data.message || 'Kayıt başarısız'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu');
    });
}
</script>

