$(document).ready(function() {
    $(".desktop").click(function() {
        let nextDesktop = $(this).next(".desktop");

        if (nextDesktop.length === 0) {
            nextDesktop = $(".desktop").first();
        }

        $('html, body').animate({
            scrollTop: nextDesktop.offset().top
        }, 800); 
    });
});