(function ($, window) {
  function openModal(html) {
    $("#admin-modal").html(html);
    $("body").addClass("modal-open");
    window.AdminFormWidgets.init($("#admin-modal"));
  }

  function closeModal() {
    $("#admin-modal").empty();
    $("body").removeClass("modal-open");
  }

  function refreshEntityTable() {
    var shell = $("#entity-table-shell");
    if (!shell.length) {
      return;
    }

    window.AppUi.fetchHtml(window.location.pathname + window.location.search, "#entity-table-shell");
  }

  $(document).on("submit", "[data-entity-search]", function (event) {
    var form = $(this);
    var baseUrl = window.location.pathname;
    var query = form.serialize();

    event.preventDefault();
    window.AppUi.fetchHtml(baseUrl + "?" + query, "#entity-table-shell", function () {
      window.AppUi.syncHistory(baseUrl + "?" + query);
    });
  });

  $(document).on("click", "[data-entity-page]", function (event) {
    event.preventDefault();

    var url = $(this).attr("href");
    window.AppUi.fetchHtml(url, "#entity-table-shell", function () {
      window.AppUi.syncHistory(url);
    });
  });

  $(document).on("click", "[data-modal-open]", function (event) {
    event.preventDefault();

    $.ajax({
      url: $(this).attr("href"),
      type: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      success: function (response) {
        if (response.html) {
          openModal(response.html);
        }
      }
    });
  });

  $(document).on("click", "[data-modal-close]", function (event) {
    event.preventDefault();
    closeModal();
  });

  $(document).on("click", ".admin-modal", function (event) {
    if ($(event.target).is(".admin-modal")) {
      closeModal();
    }
  });

  $(document).on("submit", "[data-modal-form]", function (event) {
    var form = $(this);
    event.preventDefault();

    form.find("[data-currency-display]").each(function () {
      window.AdminFormWidgets.updateCurrencyFieldFromDisplay(this);
    });

    $.ajax({
      url: form.attr("action"),
      type: "POST",
      data: new FormData(form[0]),
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.html) {
          openModal(response.html);
          return;
        }

        closeModal();
        refreshEntityTable();

        if (response.message) {
          window.AppUi.showAdminSuccessAlert(response.message);
        }
      },
      error: function (xhr) {
        if (xhr.responseJSON && xhr.responseJSON.html) {
          openModal(xhr.responseJSON.html);
          return;
        }

        window.AppUi.showFlashMessage("Terjadi kesalahan saat memproses data.", "danger");
      }
    });
  });
})(jQuery, window);
