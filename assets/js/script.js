( function ( $ ) {
    $( document ).ready( function () {

        // Tooltips
        $('.kpap-tooltip').tooltip({
            content: function () {
                return $(this).prop('title');
            },
            tooltipClass: "kpap-tooltip-text",
            position: {
                my: 'center top',
                at: 'center bottom+10',
                collision: 'flipfit'
            },
            hide: {
                duration: 500
            },
            show: {
                duration: 500
            }
        });
    });
})(jQuery);
