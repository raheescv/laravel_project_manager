$(document).ready(function () {
    if ($('.pos-category').length > 0) {
        $('.pos-category').owlCarousel({
            items: 6,
            loop: false,
            margin: 8,
            nav: true,
            dots: false,
            autoplay: false,
            smartSpeed: 1000,
            navText: ['<i class="fas fa-chevron-left"></i>', '<i class="fas fa-chevron-right"></i>'],
            responsive: {
                0: {
                    items: 2
                },
                500: {
                    items: 3
                },
                768: {
                    items: 4
                },
                991: {
                    items: 5
                },
                1200: {
                    items: 6
                },
                1401: {
                    items: 6
                }
            }
        })
    }
});


