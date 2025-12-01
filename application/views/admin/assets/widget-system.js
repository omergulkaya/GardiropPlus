/**
 * Widget System - Drag & Drop, Customizable Dashboard
 */

(function() {
    'use strict';

    const WidgetSystem = {
        widgets: [],
        grid: null,

        init() {
            this.loadWidgets();
            this.initDragAndDrop();
            this.initRealTimeUpdates();
            this.bindEvents();
        },

        loadWidgets() {
            const savedLayout = localStorage.getItem('dashboard-layout');
            if (savedLayout) {
                try {
                    const layout = JSON.parse(savedLayout);
                    this.applyLayout(layout);
                } catch (e) {
                    console.error('Failed to load saved layout:', e);
                }
            }
        },

        initDragAndDrop() {
            // Sortable.js veya benzer bir kütüphane kullanılabilir
            // Basit implementasyon için HTML5 Drag & Drop API kullanıyoruz
            const widgetContainer = document.querySelector('.widget-container');
            if (!widgetContainer) return;

            const widgets = widgetContainer.querySelectorAll('[data-widget]');
            widgets.forEach(widget => {
                widget.draggable = true;
                widget.addEventListener('dragstart', (e) => this.handleDragStart(e));
                widget.addEventListener('dragover', (e) => this.handleDragOver(e));
                widget.addEventListener('drop', (e) => this.handleDrop(e));
                widget.addEventListener('dragend', (e) => this.handleDragEnd(e));
            });
        },

        handleDragStart(e) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.target.outerHTML);
            e.target.classList.add('dragging');
        },

        handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            return false;
        },

        handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }

            const draggedElement = document.querySelector('.dragging');
            const dropTarget = e.currentTarget;

            if (draggedElement !== dropTarget) {
                const allElements = Array.from(dropTarget.parentNode.children);
                const draggedIndex = allElements.indexOf(draggedElement);
                const dropIndex = allElements.indexOf(dropTarget);

                if (draggedIndex < dropIndex) {
                    dropTarget.parentNode.insertBefore(draggedElement, dropTarget.nextSibling);
                } else {
                    dropTarget.parentNode.insertBefore(draggedElement, dropTarget);
                }
            }

            this.saveLayout();
            return false;
        },

        handleDragEnd(e) {
            e.target.classList.remove('dragging');
        },

        saveLayout() {
            const widgets = document.querySelectorAll('[data-widget]');
            const layout = Array.from(widgets).map((widget, index) => ({
                id: widget.getAttribute('data-widget-id'),
                order: index
            }));
            localStorage.setItem('dashboard-layout', JSON.stringify(layout));
        },

        applyLayout(layout) {
            const widgetContainer = document.querySelector('.widget-container');
            if (!widgetContainer) return;

            layout.sort((a, b) => a.order - b.order);
            layout.forEach(item => {
                const widget = widgetContainer.querySelector(`[data-widget-id="${item.id}"]`);
                if (widget) {
                    widgetContainer.appendChild(widget);
                }
            });
        },

        initRealTimeUpdates() {
            // Polling ile real-time updates (WebSocket alternatifi)
            setInterval(() => {
                this.updateWidgets();
            }, 30000); // 30 saniyede bir güncelle
        },

        async updateWidgets() {
            const widgets = document.querySelectorAll('[data-widget][data-auto-update="true"]');
            widgets.forEach(async (widget) => {
                const widgetId = widget.getAttribute('data-widget-id');
                try {
                    const response = await fetch(`/admin/api/widget_data/${widgetId}`);
                    const data = await response.json();
                    this.updateWidgetContent(widget, data);
                } catch (error) {
                    console.error(`Failed to update widget ${widgetId}:`, error);
                }
            });
        },

        updateWidgetContent(widget, data) {
            // Widget içeriğini güncelle
            const contentArea = widget.querySelector('.widget-content');
            if (contentArea && data.html) {
                contentArea.innerHTML = data.html;
            }
        },

        bindEvents() {
            // Widget ayarları
            document.addEventListener('click', (e) => {
                if (e.target.matches('.widget-settings')) {
                    this.showWidgetSettings(e.target.closest('[data-widget]'));
                }
                if (e.target.matches('.widget-remove')) {
                    this.removeWidget(e.target.closest('[data-widget]'));
                }
            });
        },

        showWidgetSettings(widget) {
            // Widget ayarları modal'ı göster
            console.log('Show settings for widget:', widget.getAttribute('data-widget-id'));
        },

        removeWidget(widget) {
            if (window.Confirm) {
                window.Confirm.show('Bu widget\'ı kaldırmak istediğinize emin misiniz?', () => {
                    widget.remove();
                    this.saveLayout();
                    if (window.Toast) {
                        window.Toast.success('Widget kaldırıldı');
                    }
                });
            }
        }
    };

    // Export functionality
    const ExportManager = {
        async exportPDF(elementId, filename) {
            // html2pdf.js veya benzer kütüphane kullanılabilir
            const element = document.getElementById(elementId);
            if (!element) return;

            try {
                const { jsPDF } = await import('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js');
                const pdf = new jsPDF();
                pdf.html(element, {
                    callback: (doc) => {
                        doc.save(filename || 'export.pdf');
                    }
                });
            } catch (error) {
                console.error('PDF export error:', error);
                if (window.Toast) {
                    window.Toast.error('PDF dışa aktarma başarısız');
                }
            }
        },

        exportExcel(data, filename) {
            // SheetJS veya benzer kütüphane kullanılabilir
            const csv = this.convertToCSV(data);
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename || 'export.csv';
            link.click();
        },

        convertToCSV(data) {
            if (!Array.isArray(data) || data.length === 0) return '';

            const headers = Object.keys(data[0]);
            const rows = data.map(row => 
                headers.map(header => {
                    const value = row[header];
                    return typeof value === 'string' ? `"${value.replace(/"/g, '""')}"` : value;
                }).join(',')
            );

            return [headers.join(','), ...rows].join('\n');
        }
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        WidgetSystem.init();
        window.WidgetSystem = WidgetSystem;
        window.ExportManager = ExportManager;
    });

})();

