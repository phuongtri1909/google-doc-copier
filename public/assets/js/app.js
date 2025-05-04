document.addEventListener('DOMContentLoaded', function() {
    // Animating elements when they come into view
    function animateOnScroll() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const rect = element.getBoundingClientRect();
            const isVisible = (rect.top <= window.innerHeight * 0.8);
            
            if (isVisible) {
                const animation = element.dataset.animation || 'fadeIn';
                element.classList.add('animated', animation);
            }
        });
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Add animation to alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Add close event
        if (alert.querySelector('.btn-close')) {
            alert.querySelector('.btn-close').addEventListener('click', function() {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            });
        }
        
        // Auto close non-error alerts after 5 seconds
        if (!alert.classList.contains('alert-danger') && !alert.classList.contains('alert-warning')) {
            setTimeout(() => {
                if (alert.querySelector('.btn-close')) {
                    alert.querySelector('.btn-close').click();
                } else {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                }
            }, 5000);
        }
    });
    
    // Add scroll event listener
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll();
});