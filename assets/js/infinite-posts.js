jQuery(document).ready(function($) {
    let fypip_loading = false;

    // Click sur bouton "Charger plus"
    $('.fypip_load-more-btn').on('click', function(e) {
        e.preventDefault();
        const $wrapper = $(this).closest('.fypip_wrapper');
        fypip_loadMore($wrapper);
    });

    // Observer d'intersection (scroll)
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
        if (entry.isIntersecting) {
            const $target = $(entry.target);
            const $wrapper = $target.closest('.fypip_wrapper');
            fypip_loadMore($wrapper);
        }
        });
    }, {
        root: null,
        rootMargin: '0px 0px -100px 0px',
        threshold: 0.1
    });

    // Observer tous les éléments de type .fyp_loader
    $('.fypip_scroll-marker').each(function() {
        observer.observe(this);
    });

    // Fonction de chargement AJAX
    function fypip_loadMore($wrapper) {console.log('Loading more posts');
        if (fypip_loading) return;
        fypip_loading = true;

        let id = $wrapper.data('id');
        let post_type = $wrapper.data('post_type') || 'post';
        let category = $wrapper.data('category') || 0;
        let page = $wrapper.data('page') || 1;
        let posts_per_page = $wrapper.data('posts_per_page') || 10;
        let offset = $wrapper.data('offset') || 0;
        let orderby = $wrapper.data('orderby') || 'date';
        let order = $wrapper.data('order') || 'DESC';

        $.ajax({
            url: fypinpo_infinite_posts.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'fypinpo_load_more',
                nonce: fypinpo_infinite_posts.nonce,
                post_type: post_type,
                category: category,
                page: page,
                posts_per_page: posts_per_page,
                offset: offset,
                orderby: orderby,
                order: order
            },
            beforeSend: function() {
                $wrapper.find('.fypip_loader').show();
                $wrapper.find('.fypip_load-more-btn').hide();
            },
            success: function(response) {
                if (response.success && response.data.html && response.data.html.trim() !== "") {
                    $wrapper.find('.fypip_posts').append(response.data.html);
                    $wrapper.data('page', page + 1);
                }
                else {
                    $wrapper.find('.fypip_load-more-btn').remove();
                    const loader = $wrapper.find('.fypip_loader').get(0);
                    if (loader) observer.unobserve(loader);
                    $wrapper.find('.fypip_loader').remove();
                    $wrapper.find('.fypip_scroll-marker').remove();
                    $wrapper.find('.fypip_end-message').show();
                }
            },
            error: function(e) {
                console.error('AJAX error:', e);
            },
            complete: function() {console.log('AJAX request completed');
                fypip_loading = false;
                $wrapper.find('.fypip_loader').hide();
                $wrapper.find('.fypip_load-more-btn').show();
            }
        });
    }
});
