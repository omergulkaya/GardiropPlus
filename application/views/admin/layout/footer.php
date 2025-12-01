    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php
    // CSS/JS Optimizasyonu: Production'da birleştirilmiş ve minify edilmiş versiyonları kullan
    if (ENVIRONMENT === 'production') {
        // Production: Birleştirilmiş ve minify edilmiş dosya
        $combined_js = base_url('application/views/admin/assets/admin-combined.min.js');
        if (file_exists(FCPATH . 'application/views/admin/assets/admin-combined.min.js')) {
            echo '<script src="' . $combined_js . '"></script>';
        } else {
            // Fallback: Ayrı dosyalar (minify edilmiş)
            echo '<script src="' . base_url('application/views/admin/assets/admin-js.min.js') . '"></script>';
            echo '<script src="' . base_url('application/views/admin/assets/widget-system.min.js') . '"></script>';
        }
    } else {
        // Development: Ayrı dosyalar (debug için)
        echo '<script src="' . base_url('application/views/admin/assets/admin-js.js') . '"></script>';
        echo '<script src="' . base_url('application/views/admin/assets/widget-system.js') . '"></script>';
    }
    ?>
    
    <script>
        // Sidebar toggle for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    // Add overlay on mobile
                    if (sidebar.classList.contains('open')) {
                        const overlay = document.createElement('div');
                        overlay.className = 'sidebar-overlay';
                        overlay.style.cssText = `
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(0, 0, 0, 0.5);
                            z-index: 999;
                            backdrop-filter: blur(4px);
                        `;
                        document.body.appendChild(overlay);
                        overlay.addEventListener('click', function() {
                            sidebar.classList.remove('open');
                            overlay.remove();
                        });
                    } else {
                        const overlay = document.querySelector('.sidebar-overlay');
                        if (overlay) overlay.remove();
                    }
                });
            }
            
            // Smooth scroll for sidebar
            if (sidebar) {
                sidebar.style.scrollBehavior = 'smooth';
            }
            
            // Lazy load images
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                observer.unobserve(img);
                            }
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        });
    </script>
</body>
</html>

