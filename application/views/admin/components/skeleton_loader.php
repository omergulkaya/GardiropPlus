<?php
/**
 * Skeleton Loader Component
 * Loading states için skeleton screens
 */
$type = isset($type) ? $type : 'default';
$count = isset($count) ? $count : 1;
?>

<?php if ($type === 'table'): ?>
    <!-- Table Skeleton -->
    <?php for ($i = 0; $i < $count; $i++): ?>
    <tr>
        <td><div class="skeleton" style="height: 20px; width: 100%;"></div></td>
        <td><div class="skeleton" style="height: 20px; width: 80%;"></div></td>
        <td><div class="skeleton" style="height: 20px; width: 60%;"></div></td>
        <td><div class="skeleton" style="height: 20px; width: 40%;"></div></td>
    </tr>
    <?php endfor; ?>

<?php elseif ($type === 'card'): ?>
    <!-- Card Skeleton -->
    <?php for ($i = 0; $i < $count; $i++): ?>
    <div class="stat-card">
        <div class="skeleton" style="height: 24px; width: 60%; margin-bottom: 12px;"></div>
        <div class="skeleton" style="height: 16px; width: 100%; margin-bottom: 8px;"></div>
        <div class="skeleton" style="height: 16px; width: 80%;"></div>
    </div>
    <?php endfor; ?>

<?php elseif ($type === 'list'): ?>
    <!-- List Skeleton -->
    <?php for ($i = 0; $i < $count; $i++): ?>
    <div class="d-flex align-items-center mb-3">
        <div class="skeleton" style="height: 40px; width: 40px; border-radius: 50%; margin-right: 12px;"></div>
        <div class="flex-grow-1">
            <div class="skeleton" style="height: 16px; width: 60%; margin-bottom: 8px;"></div>
            <div class="skeleton" style="height: 14px; width: 40%;"></div>
        </div>
    </div>
    <?php endfor; ?>

<?php else: ?>
    <!-- Default Skeleton -->
    <div class="skeleton" style="height: 20px; width: 100%; margin-bottom: 8px;"></div>
    <div class="skeleton" style="height: 20px; width: 80%;"></div>
<?php endif; ?>

