jQuery(document).ready(function ($) {
  let fypinpo_loading = false;

  // Click sur bouton "Charger plus"
  $(".fypinpo_load-more-btn").on("click", function (e) {
    e.preventDefault();
    const $wrapper = $(this).closest(".fypinpo_wrapper");
    fypinpo_loadMore($wrapper);
  });

  // Observer d'intersection (scroll)
  const observer = new IntersectionObserver(
    function (entries) {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const $target = $(entry.target);
          const $wrapper = $target.closest(".fypinpo_wrapper");
          fypinpo_loadMore($wrapper);
        }
      });
    },
    {
      root: null,
      rootMargin: "0px 0px -100px 0px",
      threshold: 0.1,
    }
  );

  // Observer tous les éléments de type .fyp_loader
  $(".fypinpo_scroll-marker").each(function () {
    observer.observe(this);
  });

  // Fonction de chargement AJAX
  function fypinpo_loadMore($wrapper) {
    if (fypinpo_loading) return;
    fypinpo_loading = true;

    let id = $wrapper.data("id");
    let post_type = $wrapper.data("post_type") || "post";
    let category = $wrapper.data("category") || 0;
    let taxonomy = $wrapper.data("taxonomy") || "";
    let term = $wrapper.data("term") || "";
    let page = $wrapper.data("page") || 1;
    let posts_per_page = $wrapper.data("posts_per_page") || 10;
    let offset = $wrapper.data("offset") || 0;
    let orderby = $wrapper.data("orderby") || "date";
    let order = $wrapper.data("order") || "DESC";

    $.ajax({
      url: fypinpo_infinite_posts.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "fypinpo_load_more",
        nonce: fypinpo_infinite_posts.nonce,
        post_type: post_type,
        category: category,
        taxonomy: taxonomy,
        term: term,
        page: page,
        posts_per_page: posts_per_page,
        offset: offset,
        orderby: orderby,
        order: order,
      },
      beforeSend: function () {
        $wrapper.find(".fypinpo_loader").show();
        $wrapper.find(".fypinpo_load-more-btn").hide();
      },
      success: function (response) {
        if (
          response.success &&
          response.data.html &&
          response.data.html.trim() !== ""
        ) {
          $wrapper.find(".fypinpo_posts").append(response.data.html);
          $wrapper.data("page", page + 1);
        } else {
          $wrapper.find(".fypinpo_load-more-btn").remove();
          const loader = $wrapper.find(".fypinpo_loader").get(0);
          if (loader) observer.unobserve(loader);
          $wrapper.find(".fypinpo_loader").remove();
          $wrapper.find(".fypinpo_scroll-marker").remove();
          $wrapper.find(".fypinpo_end-message").show();
        }
      },
      error: function (e) {
        console.error("AJAX error:", e);
      },
      complete: function () {
        fypinpo_loading = false;
        $wrapper.find(".fypinpo_loader").hide();
        $wrapper.find(".fypinpo_load-more-btn").show();
      },
    });
  }
});
