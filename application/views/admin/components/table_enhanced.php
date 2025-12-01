<?php
/**
 * Enhanced Table Component
 * Sıralanabilir, yeniden boyutlandırılabilir, bulk selection
 */
$table_id = isset($table_id) ? $table_id : 'enhanced-table';
$columns = isset($columns) ? $columns : [];
$data = isset($data) ? $data : [];
$bulk_actions = isset($bulk_actions) ? $bulk_actions : [];
?>

<div class="table-controls mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <?php if (!empty($bulk_actions)): ?>
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all-<?php echo $table_id; ?>">
                    <i class="bi bi-check-square"></i> Tümünü Seç
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all-<?php echo $table_id; ?>">
                    <i class="bi bi-square"></i> Seçimi Kaldır
                </button>
            </div>
            <div class="btn-group">
                <?php foreach ($bulk_actions as $action): ?>
                <button type="button" class="btn btn-sm btn-<?php echo $action['class'] ?? 'primary'; ?>" 
                        onclick="bulkAction('<?php echo $action['action']; ?>', '<?php echo $table_id; ?>')">
                    <i class="bi bi-<?php echo $action['icon'] ?? 'check'; ?>"></i> <?php echo $action['label']; ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportTable('<?php echo $table_id; ?>', 'csv')">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportTable('<?php echo $table_id; ?>', 'excel')">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </button>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover" id="<?php echo $table_id; ?>">
        <thead>
            <tr>
                <?php if (!empty($bulk_actions)): ?>
                <th width="40">
                    <input type="checkbox" class="form-check-input" id="check-all-<?php echo $table_id; ?>">
                </th>
                <?php endif; ?>
                <?php foreach ($columns as $col): ?>
                <th data-sortable="<?php echo isset($col['sortable']) && $col['sortable'] ? 'true' : 'false'; ?>"
                    data-column="<?php echo $col['key']; ?>"
                    style="cursor: <?php echo isset($col['sortable']) && $col['sortable'] ? 'pointer' : 'default'; ?>;">
                    <?php echo $col['label']; ?>
                    <?php if (isset($col['sortable']) && $col['sortable']): ?>
                    <i class="bi bi-arrow-down-up ms-1"></i>
                    <?php endif; ?>
                </th>
                <?php endforeach; ?>
                <th width="100">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
            <tr>
                <td colspan="<?php echo count($columns) + (empty($bulk_actions) ? 1 : 2); ?>" class="text-center text-muted">
                    Veri bulunamadı
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($data as $row): ?>
            <tr data-id="<?php echo $row['id'] ?? ''; ?>">
                <?php if (!empty($bulk_actions)): ?>
                <td>
                    <input type="checkbox" class="form-check-input row-checkbox" value="<?php echo $row['id'] ?? ''; ?>">
                </td>
                <?php endif; ?>
                <?php foreach ($columns as $col): ?>
                <td data-column="<?php echo $col['key']; ?>">
                    <?php 
                    $value = $row[$col['key']] ?? '';
                    if (isset($col['format']) && is_callable($col['format'])) {
                        echo call_user_func($col['format'], $value, $row);
                    } else {
                        echo htmlspecialchars($value);
                    }
                    ?>
                </td>
                <?php endforeach; ?>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" onclick="editRow(<?php echo $row['id'] ?? ''; ?>)" title="Düzenle">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteRow(<?php echo $row['id'] ?? ''; ?>)" title="Sil">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Table sorting
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('<?php echo $table_id; ?>');
    if (!table) return;
    
    const headers = table.querySelectorAll('th[data-sortable="true"]');
    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            const isAsc = this.classList.contains('sort-asc');
            
            // Remove all sort classes
            headers.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            
            // Add sort class
            this.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
            
            // Sort table
            sortTable(table, column, !isAsc);
        });
    });
    
    // Bulk selection
    const checkAll = document.getElementById('check-all-<?php echo $table_id; ?>');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            const checkboxes = table.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }
});

function sortTable(table, column, ascending) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = a.querySelector(`td[data-column="${column}"]`)?.textContent.trim() || '';
        const bVal = b.querySelector(`td[data-column="${column}"]`)?.textContent.trim() || '';
        
        if (ascending) {
            return aVal.localeCompare(bVal);
        } else {
            return bVal.localeCompare(aVal);
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

function bulkAction(action, tableId) {
    const table = document.getElementById(tableId);
    const selected = Array.from(table.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        if (window.Toast) {
            window.Toast.warning('Lütfen en az bir öğe seçin');
        }
        return;
    }
    
    if (window.Confirm) {
        window.Confirm.show(`Seçili ${selected.length} öğe için "${action}" işlemini yapmak istediğinize emin misiniz?`, () => {
            // AJAX call to perform bulk action
            fetch('/admin/api/bulk_action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    ids: selected
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (window.Toast) {
                        window.Toast.success('İşlem başarıyla tamamlandı');
                    }
                    location.reload();
                } else {
                    if (window.Toast) {
                        window.Toast.error(data.message || 'İşlem başarısız');
                    }
                }
            });
        });
    }
}

function exportTable(tableId, format) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    if (format === 'csv') {
        if (window.ExportManager) {
            const data = Array.from(table.querySelectorAll('tbody tr')).map(row => {
                const cells = row.querySelectorAll('td:not(:last-child)');
                return Array.from(cells).map(cell => cell.textContent.trim());
            });
            window.ExportManager.exportExcel(data, 'export.csv');
        }
    }
}
</script>

