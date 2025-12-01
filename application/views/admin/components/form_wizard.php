<?php
/**
 * Form Wizard Component
 * Çok adımlı formlar için
 */
$steps = isset($steps) ? $steps : [];
$form_id = isset($form_id) ? $form_id : 'form-wizard';
$current_step = isset($current_step) ? $current_step : 0;
?>

<div class="form-wizard" id="<?php echo $form_id; ?>">
    <!-- Progress Bar -->
    <div class="wizard-progress mb-4">
        <div class="progress" style="height: 4px;">
            <div class="progress-bar" role="progressbar" 
                 style="width: <?php echo (($current_step + 1) / count($steps)) * 100; ?>%;"
                 aria-valuenow="<?php echo $current_step + 1; ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="<?php echo count($steps); ?>">
            </div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <?php foreach ($steps as $index => $step): ?>
            <div class="wizard-step <?php echo $index <= $current_step ? 'completed' : ''; ?> <?php echo $index === $current_step ? 'active' : ''; ?>">
                <div class="wizard-step-number"><?php echo $index + 1; ?></div>
                <div class="wizard-step-label"><?php echo $step['label']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Form Steps -->
    <form id="<?php echo $form_id; ?>-form">
        <?php foreach ($steps as $index => $step): ?>
        <div class="wizard-step-content <?php echo $index === $current_step ? 'active' : ''; ?>" data-step="<?php echo $index; ?>">
            <h5 class="mb-4"><?php echo $step['title']; ?></h5>
            <?php if (isset($step['content'])): ?>
                <?php echo $step['content']; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <!-- Navigation Buttons -->
        <div class="wizard-navigation mt-4">
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="wizard-prev" 
                        <?php echo $current_step === 0 ? 'disabled' : ''; ?>>
                    <i class="bi bi-arrow-left"></i> Önceki
                </button>
                <div>
                    <?php if ($current_step < count($steps) - 1): ?>
                    <button type="button" class="btn btn-primary" id="wizard-next">
                        Sonraki <i class="bi bi-arrow-right"></i>
                    </button>
                    <?php else: ?>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check"></i> Tamamla
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.wizard-step {
    text-align: center;
    flex: 1;
    position: relative;
}

.wizard-step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--color-gray-200);
    color: var(--color-gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-weight: var(--font-weight-bold);
    transition: all var(--transition-base);
}

.wizard-step.active .wizard-step-number {
    background: var(--color-primary-500);
    color: white;
    transform: scale(1.1);
}

.wizard-step.completed .wizard-step-number {
    background: var(--color-success);
    color: white;
}

.wizard-step-label {
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.wizard-step.active .wizard-step-label {
    color: var(--color-primary-500);
    font-weight: var(--font-weight-medium);
}

.wizard-step-content {
    display: none;
}

.wizard-step-content.active {
    display: block;
    animation: fadeIn var(--transition-base);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wizard = document.getElementById('<?php echo $form_id; ?>');
    if (!wizard) return;
    
    let currentStep = <?php echo $current_step; ?>;
    const totalSteps = <?php echo count($steps); ?>;
    
    const prevBtn = wizard.querySelector('#wizard-prev');
    const nextBtn = wizard.querySelector('#wizard-next');
    
    function updateStep(step) {
        // Hide all steps
        wizard.querySelectorAll('.wizard-step-content').forEach((content, index) => {
            content.classList.toggle('active', index === step);
        });
        
        // Update progress bar
        const progressBar = wizard.querySelector('.progress-bar');
        progressBar.style.width = ((step + 1) / totalSteps) * 100 + '%';
        progressBar.setAttribute('aria-valuenow', step + 1);
        
        // Update step indicators
        wizard.querySelectorAll('.wizard-step').forEach((stepEl, index) => {
            stepEl.classList.toggle('active', index === step);
            stepEl.classList.toggle('completed', index < step);
        });
        
        // Update buttons
        if (prevBtn) {
            prevBtn.disabled = step === 0;
        }
        
        if (nextBtn) {
            if (step === totalSteps - 1) {
                nextBtn.style.display = 'none';
            } else {
                nextBtn.style.display = 'inline-block';
            }
        }
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentStep > 0) {
                currentStep--;
                updateStep(currentStep);
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            // Validate current step
            const currentContent = wizard.querySelector(`.wizard-step-content[data-step="${currentStep}"]`);
            const form = currentContent.closest('form');
            
            if (form && form.checkValidity()) {
                if (currentStep < totalSteps - 1) {
                    currentStep++;
                    updateStep(currentStep);
                }
            } else {
                form.reportValidity();
            }
        });
    }
});
</script>

