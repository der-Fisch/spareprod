(function ($, window) {
  function formatPriceInput(input) {
    var digits = ($(input).val() || "").replace(/\D/g, "");
    $(input).val(digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "");
  }

  function normalizeFilterForm(form) {
    $(form).find("[data-price-format]").each(function () {
      $(this).val(($(this).val() || "").replace(/\D/g, ""));
    });
  }

  function bindCatalogFilters() {
    $(document).on("input", "[data-price-format]", function () {
      formatPriceInput(this);
    });

    $(document).on("submit", ".catalog-filter-form", function () {
      normalizeFilterForm(this);
    });
  }

  function bindCatalogPagination() {
    $(document).on("click", "[data-catalog-page]", function (event) {
      event.preventDefault();

      var url = $(this).attr("href");
      window.AppUi.fetchHtml(url, "#catalog-results", function () {
        window.AppUi.syncHistory(url);
      });
    });
  }

  $(document).ready(function () {
    bindCatalogFilters();
    bindCatalogPagination();

    $("[data-price-format]").each(function () {
      formatPriceInput(this);
    });
  });
})(jQuery, window);
