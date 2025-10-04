jQuery(document).ready(function($) {
    
    // Add copy to clipboard functionality for codes
    $('code').each(function() {
        const $code = $(this);
        const text = $code.text();
        
        // Skip if text is too short (probably not a credential)
        if (text.length < 10) return;
        
        const $copyBtn = $('<button class="button button-small copy-button" type="button">Copy</button>');
        
        $copyBtn.on('click', function(e) {
            e.preventDefault();
            
            // Create temporary textarea to copy text
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Show success feedback
            const originalText = $copyBtn.text();
            $copyBtn.text('Copied!').addClass('copy-success');
            
            setTimeout(function() {
                $copyBtn.text(originalText).removeClass('copy-success');
            }, 2000);
        });
        
        $code.after($copyBtn);
    });
    
    // Form validation
    $('.oauth2-client-form').on('submit', function(e) {
        const clientName = $('#client_name').val().trim();
        const redirectUri = $('#redirect_uri').val().trim();
        
        if (!clientName) {
            alert('Please enter a client name.');
            e.preventDefault();
            return false;
        }
        
        if (!redirectUri) {
            alert('Please enter a redirect URI.');
            e.preventDefault();
            return false;
        }
        
        // Basic URL validation
        try {
            new URL(redirectUri);
        } catch (e) {
            alert('Please enter a valid redirect URI.');
            e.preventDefault();
            return false;
        }
    });
    
    // Toggle client type explanation
    $('#is_confidential').on('change', function() {
        const isConfidential = $(this).is(':checked');
        const $description = $(this).siblings('.description');
        
        if (isConfidential) {
            $description.html('Confidential clients can securely store a client secret. Use this for server-side applications.');
        } else {
            $description.html('Public clients cannot securely store secrets and must use PKCE. Use this for mobile apps and single-page applications.');
        }
    });
    
});
