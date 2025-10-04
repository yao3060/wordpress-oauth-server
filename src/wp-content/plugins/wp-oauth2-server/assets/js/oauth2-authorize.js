// OAuth2 Authorization Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Add loading states to buttons
    const form = document.querySelector('.oauth2-form');
    if (form) {
        const buttons = form.querySelectorAll('button[type="submit"]');
        
        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                // Disable all buttons to prevent double submission
                buttons.forEach(function(btn) {
                    btn.disabled = true;
                });
                
                // Add loading text
                const originalText = this.textContent;
                if (this.name === 'approve') {
                    this.textContent = 'Authorizing...';
                } else if (this.name === 'deny') {
                    this.textContent = 'Denying...';
                }
                
                // Re-enable after a delay in case of errors
                setTimeout(function() {
                    buttons.forEach(function(btn) {
                        btn.disabled = false;
                        btn.textContent = btn.name === 'approve' ? 'Authorize' : 'Deny';
                    });
                }, 5000);
            });
        });
    }
    
    // Add smooth animations
    const container = document.querySelector('.oauth2-container');
    if (container) {
        container.style.opacity = '0';
        container.style.transform = 'translateY(20px)';
        container.style.transition = 'all 0.3s ease';
        
        setTimeout(function() {
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Add focus management for accessibility
    const firstButton = document.querySelector('.form-actions button');
    if (firstButton) {
        firstButton.focus();
    }
    
});
