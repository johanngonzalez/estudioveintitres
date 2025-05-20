jQuery(document).ready(function($){
    $('.pf-payment-button').on('click', function(e){
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true);

        $.post(pfParams.ajaxurl, {
            action: 'pf_create_payment_link',
            amount: button.data('amount'),
            description: button.data('description'),
            invoice: button.data('invoice') || '',
            nonce: pfParams.nonce
        }, function(response){
            if(response.success && response.data.url){
                window.location.href = response.data.url;
            } else {
                alert(response.data || 'Error processing payment');
                button.prop('disabled', false);
            }
        });
    });
});
