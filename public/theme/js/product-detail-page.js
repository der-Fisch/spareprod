(function ($) {
  function setupGallery() {
    var gallery = $("[data-product-gallery]");
    if (!gallery.length) {
      return;
    }

    var slides = gallery.find("[data-gallery-slide]");
    if (slides.length <= 1) {
      return;
    }

    var activeIndex = 0;

    function renderGallery(index) {
      activeIndex = (index + slides.length) % slides.length;
      slides.removeClass("is-active").eq(activeIndex).addClass("is-active");
    }

    gallery.on("click", "[data-gallery-prev]", function () {
      renderGallery(activeIndex - 1);
    });

    gallery.on("click", "[data-gallery-next]", function () {
      renderGallery(activeIndex + 1);
    });
  }

  $(document).on("submit", "[data-cart-add-form]", function (event) {
    event.preventDefault();

    var form = $(this);
    var submitButton = form.find("button[type='submit']");

    submitButton.prop("disabled", true);

    $.ajax({
      type: form.attr("method") || "POST",
      url: form.attr("action"),
      data: form.serialize(),
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      success: function (response) {
        window.AppUi.updateCartItemCount();
        window.AppUi.showCartSuccessAlert(response.flash_message || "Product berhasil ditambahkan ke cart.");
      },
      error: function () {
        form.off("submit");
        form.trigger("submit");
      },
      complete: function () {
        submitButton.prop("disabled", false);
      }
    });
  });

  $(document).ready(function () {
    setupGallery();
  });
})(jQuery);
