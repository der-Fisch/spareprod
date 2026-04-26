(function (window, $) {
  function formatRupiah(value) {
    if (value === undefined || value === null || value === "") {
      return "Rp0";
    }

    var numeric = parseFloat(String(value).replace(/[^0-9.-]/g, ""));
    if (isNaN(numeric)) {
      return "Rp0";
    }

    return "Rp" + Math.round(numeric).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function formatCatalogRupiah(value) {
    if (value === undefined || value === null || value === "") {
      return "Rp0";
    }

    var numeric = parseFloat(String(value).replace(/[^0-9.-]/g, ""));
    if (isNaN(numeric)) {
      return "Rp0";
    }

    return "Rp" + Math.round(numeric * 10000).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function flashContainer() {
    return $(".public-flash-stack, .backoffice-flash-stack").first();
  }

  function showFlashMessage(message, type) {
    var container = flashContainer();
    if (!container.length || !message) {
      return;
    }

    var alertType = type || "success";
    var alertHtml = [
      "<div class='alert alert-" + alertType + " alert-dismissible flash-banner js-flash-message' role='alert'>",
      "<button type='button' class='close' data-dismiss='alert' aria-label='Close'>",
      "<span aria-hidden='true'>&times;</span>",
      "</button>",
      message,
      "</div>"
    ].join("");

    $(".js-flash-message").remove();
    container.prepend(alertHtml);
  }

  function showCartSuccessAlert(message) {
    var text = message || "Produk berhasil ditambahkan ke keranjang.";
    if (!window.Swal) {
      showFlashMessage(text);
      return;
    }

    window.Swal.fire({
      icon: "success",
      title: "Berhasil",
      text: text,
      showCancelButton: true,
      confirmButtonText: "Lihat Keranjang",
      cancelButtonText: "Lanjut Belanja",
      reverseButtons: true
    }).then(function (result) {
      if (result.isConfirmed) {
        window.location.href = "/cart/";
      }
    });
  }

  function showAdminSuccessAlert(message) {
    var text = message || "Perubahan berhasil disimpan.";
    if (!window.Swal) {
      showFlashMessage(text);
      return;
    }

    window.Swal.fire({
      icon: "success",
      title: "Berhasil",
      text: text,
      timer: 1800,
      showConfirmButton: false
    });
  }

  function updateCartItemCount() {
    $.ajax({
      url: "/cart/count",
      type: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      success: function (response) {
        var badge = $("#cart-count-badge, [data-cart-count]").first();
        if (badge.length) {
          badge.text(response.count || 0);
        }
      }
    });
  }

  function fetchHtml(url, target, onDone) {
    $.ajax({
      url: url,
      type: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      success: function (response) {
        if (response.html) {
          $(target).html(response.html);
        }

        if (onDone) {
          onDone(response);
        }
      }
    });
  }

  function syncHistory(url) {
    if (window.history && window.history.replaceState) {
      window.history.replaceState({}, document.title, url);
    }
  }

  var AppUi = {
    formatRupiah: formatRupiah,
    formatCatalogRupiah: formatCatalogRupiah,
    showFlashMessage: showFlashMessage,
    showCartSuccessAlert: showCartSuccessAlert,
    showAdminSuccessAlert: showAdminSuccessAlert,
    updateCartItemCount: updateCartItemCount,
    fetchHtml: fetchHtml,
    syncHistory: syncHistory
  };

  window.AppUi = AppUi;
  window.formatRupiah = formatRupiah;
  window.formatCatalogRupiah = formatCatalogRupiah;
  window.showFlashMessage = showFlashMessage;
  window.showCartSuccessAlert = showCartSuccessAlert;
  window.showAdminSuccessAlert = showAdminSuccessAlert;
  window.showBackofficeSuccessAlert = showAdminSuccessAlert;
  window.updateCartItemCount = updateCartItemCount;

  $(document).ready(function () {
    if ($("#cart-count-badge, [data-cart-count]").length) {
      updateCartItemCount();
    }
  });
})(window, jQuery);
