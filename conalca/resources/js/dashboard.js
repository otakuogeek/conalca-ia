// Dashboard Enhanced Interactions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard functionality
    initializeAnimations();
    initializeInteractions();
    initializeChartLoading();
    initializeMetricsCounters();
    initializeTooltips();
    initializeProgressBars();
});

// Animate elements on scroll
function initializeAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                
                // Add staggered animation delays for child elements
                const children = entry.target.querySelectorAll('.metric-card, .dashboard-card');
                children.forEach((child, index) => {
                    setTimeout(() => {
                        child.classList.add('animate-slide-up');
                    }, index * 100);
                });
            }
        });
    }, observerOptions);

    // Observe all main sections
    document.querySelectorAll('.fade-in').forEach(el => {
        observer.observe(el);
    });
}

// Initialize interactive elements
function initializeInteractions() {
    // Module navigation
    const moduleButtons = document.querySelectorAll('[id^="module-"]');
    moduleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all modules
            moduleButtons.forEach(btn => {
                btn.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-blue-600', 'text-white');
                btn.classList.add('bg-white', 'text-gray-700');
            });
            
            // Add active class to clicked module
            this.classList.remove('bg-white', 'text-gray-700');
            this.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-blue-600', 'text-white');
            
            // Add ripple effect
            createRippleEffect(this, e);
            
            // Simulate module change (you can replace this with actual functionality)
            showNotification('Módulo cambiado correctamente', 'success');
        });
    });

    // Chart loading simulation
    const chartCanvas = document.getElementById('acquisitions');
    if (chartCanvas) {
        simulateChartLoading();
    }

    // Activity card interactions
    initializeActivityCards();
}

// Chart loading simulation
function initializeChartLoading() {
    const loadingElement = document.getElementById('chart-loading');
    if (loadingElement) {
        // Simulate loading for 2 seconds
        setTimeout(() => {
            loadingElement.style.opacity = '0';
            setTimeout(() => {
                loadingElement.style.display = 'none';
            }, 500);
        }, 2000);
    }
}

// Animate metrics counters
function initializeMetricsCounters() {
    const metricElements = document.querySelectorAll('.metric-card h2');
    
    metricElements.forEach(element => {
        const text = element.textContent;
        const match = text.match(/[\d,]+/);
        
        if (match) {
            const finalValue = parseInt(match[0].replace(/,/g, ''));
            animateCounter(element, finalValue, text);
        }
    });
}

// Counter animation function
function animateCounter(element, finalValue, originalText) {
    const duration = 2000; // 2 seconds
    const startTime = performance.now();
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const currentValue = Math.floor(finalValue * easeOutQuart);
        
        // Format the number with commas
        const formattedValue = currentValue.toLocaleString();
        element.textContent = originalText.replace(/[\d,]+/, formattedValue);
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }
    
    requestAnimationFrame(updateCounter);
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        const tooltip = createTooltip(element.dataset.tooltip);
        
        element.addEventListener('mouseenter', () => {
            document.body.appendChild(tooltip);
            positionTooltip(element, tooltip);
        });
        
        element.addEventListener('mouseleave', () => {
            if (tooltip.parentNode) {
                tooltip.parentNode.removeChild(tooltip);
            }
        });
    });
}

// Create tooltip element
function createTooltip(text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-50 bg-gray-900 text-white text-sm rounded-lg py-2 px-3 shadow-lg';
    tooltip.textContent = text;
    return tooltip;
}

// Position tooltip
function positionTooltip(element, tooltip) {
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
}

// Initialize progress bars
function initializeProgressBars() {
    const progressBars = document.querySelectorAll('.progress-fill');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bar = entry.target;
                const width = bar.style.width;
                bar.style.width = '0%';
                
                setTimeout(() => {
                    bar.style.width = width;
                }, 200);
            }
        });
    });
    
    progressBars.forEach(bar => observer.observe(bar));
}

// Activity card interactions
function initializeActivityCards() {
    const activityCards = document.querySelectorAll('.group');
    
    activityCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('scale-105');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('scale-105');
        });
    });
}

// Create ripple effect
function createRippleEffect(element, event) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');
    
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
    
    const colors = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-white',
        info: 'bg-blue-500 text-white'
    };
    
    notification.classList.add(...colors[type].split(' '));
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Slide in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Slide out after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Chart loading simulation
function simulateChartLoading() {
    // This would be replaced with actual chart.js implementation
    console.log('Chart loading simulation started');
    
    // Simulate real chart data loading
    setTimeout(() => {
        console.log('Chart data loaded successfully');
        
        // Here you would initialize your actual chart
        // Example: initializeChart();
    }, 2000);
}

// Responsive handling
function handleResponsive() {
    const isMobile = window.innerWidth < 768;
    
    if (isMobile) {
        // Adjust animations for mobile
        document.querySelectorAll('.hover-scale').forEach(el => {
            el.classList.remove('hover-scale');
        });
    }
}

// Handle window resize
window.addEventListener('resize', handleResponsive);

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    // ESC key to close modals or notifications
    if (e.key === 'Escape') {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            notification.remove();
        });
    }
    
    // Ctrl + / for help
    if (e.ctrlKey && e.key === '/') {
        e.preventDefault();
        showNotification('Atajos de teclado: ESC - Cerrar notificaciones, Ctrl+/ - Ayuda', 'info');
    }
});

// Performance monitoring
function monitorPerformance() {
    // Monitor loading time
    window.addEventListener('load', function() {
        const loadTime = performance.now();
        console.log(`Dashboard loaded in ${loadTime.toFixed(2)}ms`);
        
        if (loadTime > 3000) {
            console.warn('Dashboard loading time is above optimal threshold');
        }
    });
}

// Initialize performance monitoring
monitorPerformance();

// Export functions for external use
window.dashboardUtils = {
    showNotification,
    createRippleEffect,
    animateCounter
};

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
