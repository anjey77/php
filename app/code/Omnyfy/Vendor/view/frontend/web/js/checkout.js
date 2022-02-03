require([
    'jquery',
    'domReady!'
], function($) {
    var checkInputValues = setInterval(function () {
        var inputs = $('.checkout-index-index .input-text');
        if (inputs.length >= 8) {
            inputs.each(function() {
                if ($(this).val() != '') {
                    $(this).parents('.field').addClass('active');
                }
            })

            clearInterval(checkInputValues);
        }
    }, 200)

    $('.checkout-index-index')
        .on('focus', '.input-text', function() {
            $(this).parents('.field').addClass('active');
        })
        .on('blur', '.input-text', function() {
            if ($(this).val() == '') {
                $(this).parents('.field').removeClass('active');
            }
        })
})