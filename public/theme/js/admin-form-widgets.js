(function ($, window) {
  function parseNumeric(value) {
    var numeric = parseFloat(String(value).replace(/[^0-9.-]/g, ""));
    return isNaN(numeric) ? null : numeric;
  }

  function formatDigitsToRupiah(digits) {
    if (!digits) {
      return "";
    }

    return "Rp" + String(digits).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function catalogValueToDigits(value) {
    var numeric = parseNumeric(value);
    if (numeric === null) {
      return "";
    }

    return String(Math.round(numeric * 10000));
  }

  function digitsToCatalogValue(digits) {
    if (!digits) {
      return "";
    }

    return String(parseInt(digits, 10) / 10000);
  }

  function resolveImagePreviewSrc(path) {
    if (!path) {
      return "";
    }

    if (/^https?:\/\//i.test(path) || path.charAt(0) === "/") {
      return path;
    }

    return "/" + path.replace(/^\/+/, "");
  }

  function syncCurrencyField(field) {
    var wrapper = $(field);
    var hidden = wrapper.find("[data-currency-hidden]");
    var display = wrapper.find("[data-currency-display]");
    var scale = wrapper.data("currency-scale");
    var hiddenValue = hidden.val();
    var digits = scale === "catalog"
      ? catalogValueToDigits(hiddenValue)
      : String(hiddenValue || "").replace(/\D/g, "");

    display.val(formatDigitsToRupiah(digits));
  }

  function updateCurrencyFieldFromDisplay(input) {
    var display = $(input);
    var wrapper = display.closest("[data-currency-field]");
    var hidden = wrapper.find("[data-currency-hidden]");
    var scale = wrapper.data("currency-scale");
    var digits = (display.val() || "").replace(/\D/g, "");

    display.val(formatDigitsToRupiah(digits));

    if (!digits) {
      hidden.val("");
      return;
    }

    hidden.val(scale === "catalog" ? digitsToCatalogValue(digits) : digits);
  }

  function refreshSearchableMultiselect(wrapper) {
    var root = $(wrapper);
    var labelNode = root.find("[data-multiselect-label]");
    var placeholder = labelNode.data("multiselect-placeholder") || "Pilih opsi";
    var checked = root.find("input[type='checkbox']:checked");
    var labels = checked.map(function () {
      return $(this).siblings("span").text();
    }).get().join(", ");

    labelNode.text(labels || placeholder);
  }

  function refreshSearchableSelect(wrapper) {
    var root = $(wrapper);
    var hidden = root.find("[data-searchable-select-value]");
    var inputNode = root.find("[data-searchable-select-input]");
    var placeholder = inputNode.attr("placeholder") || "Pilih opsi";
    var selected = root.find("[data-searchable-select-option][data-option-value='" + hidden.val() + "']");
    var label = selected.data("option-text");

    inputNode.val(label || "");
    inputNode.attr("placeholder", placeholder);
    root.find("[data-searchable-select-option]").show();
  }

  function renderImagePreview(shell, src, metaText) {
    var scope = $(shell);
    var preview = scope.find("[data-image-preview]");
    var placeholder = scope.find("[data-image-placeholder]");
    var meta = scope.find("[data-image-preview-meta]");

    meta.text(metaText || "Belum ada gambar dipilih.");

    if (!src) {
      preview.attr("src", "").hide();
      placeholder.show();
      return;
    }

    preview.attr("src", src).show();
    placeholder.hide();
  }

  function updateImagePreview(input) {
    var field = $(input);
    var shell = field.closest("[data-repeater-item]");
    var fileInput = shell.find("[data-image-file-input]")[0];
    var pathInput = shell.find("[data-image-path-input]");
    var file = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
    var path = $.trim(pathInput.val() || "");
    var src = resolveImagePreviewSrc(path);

    if (file) {
      var reader = new FileReader();
      reader.onload = function (event) {
        renderImagePreview(shell, event.target.result, file.name);
      };
      reader.readAsDataURL(file);
      return;
    }

    renderImagePreview(shell, src, path || "Belum ada gambar dipilih.");
  }

  function renderRatingField(field) {
    var root = $(field);
    var hidden = root.find("[data-rating-value]");
    var rating = parseFloat(hidden.val() || 0);
    var stars = root.find(".rating-star");
    var text = root.find("[data-rating-text]");

    stars.each(function () {
      var button = $(this);
      var index = parseInt(button.data("star-index"), 10);
      var emptyIcon = button.find(".icon-empty");
      var halfIcon = button.find(".icon-half");
      var fullIcon = button.find(".icon-full");

      emptyIcon.hide();
      halfIcon.hide();
      fullIcon.hide();

      if (rating >= index) {
        fullIcon.show();
      } else if (rating >= index - 0.5) {
        halfIcon.show();
      } else {
        emptyIcon.show();
      }
    });

    text.text(rating > 0 ? rating.toFixed(1).replace(/\.0$/, "") + " / 5 bintang" : "Belum ada rating dipilih.");
  }

  function init(root) {
    var scope = $(root);

    scope.find("[data-currency-field]").each(function () {
      syncCurrencyField(this);
    });

    scope.find("[data-searchable-select]").each(function () {
      refreshSearchableSelect(this);
    });

    scope.find("[data-searchable-multiselect]").each(function () {
      refreshSearchableMultiselect(this);
    });

    scope.find("[data-rating-input]").each(function () {
      renderRatingField(this);
    });

    scope.find("[data-repeater]").each(function () {
      var repeater = $(this);
      if (!repeater.attr("data-next-index")) {
        repeater.attr("data-next-index", repeater.find("[data-repeater-item]").length);
      }
    });

    scope.find("[data-image-path-input]").each(function () {
      updateImagePreview(this);
    });
  }

  $(document).on("input change", "[data-currency-display]", function () {
    updateCurrencyFieldFromDisplay(this);
  });

  $(document).on("focus click", "[data-searchable-select-input]", function (event) {
    event.preventDefault();

    var input = $(this);
    var wrapper = input.closest("[data-searchable-select]");
    $(".searchable-multiselect").removeClass("is-open");
    $(".searchable-select").not(wrapper).removeClass("is-open");
    wrapper.addClass("is-open");

    if (event.type === "click" && input.val()) {
      input.trigger("select");
    }
  });

  $(document).on("input", "[data-searchable-select-input]", function () {
    var wrapper = $(this).closest("[data-searchable-select]");
    var term = ($(this).val() || "").toLowerCase();
    wrapper.addClass("is-open");

    wrapper.find("[data-searchable-select-option]").each(function () {
      var option = $(this);
      option.toggle((option.data("option-label") || "").indexOf(term) !== -1);
    });
  });

  $(document).on("click", "[data-searchable-select-option]", function () {
    var option = $(this);
    var wrapper = option.closest("[data-searchable-select]");

    wrapper.find("[data-searchable-select-value]").val(option.data("option-value"));
    refreshSearchableSelect(wrapper);
    wrapper.find("[data-searchable-select-input]").trigger("blur");
    wrapper.removeClass("is-open");
  });

  $(document).on("click", "[data-multiselect-trigger]", function (event) {
    event.preventDefault();

    var wrapper = $(this).closest("[data-searchable-multiselect]");
    $(".searchable-select").removeClass("is-open");
    $(".searchable-multiselect").not(wrapper).removeClass("is-open");
    wrapper.toggleClass("is-open");
  });

  $(document).on("input", "[data-multiselect-search]", function () {
    var term = ($(this).val() || "").toLowerCase();
    $(this).closest("[data-searchable-multiselect]").find("[data-option-label]").each(function () {
      var option = $(this);
      option.toggle(option.data("option-label").indexOf(term) !== -1);
    });
  });

  $(document).on("change", "[data-searchable-multiselect] input[type='checkbox']", function () {
    refreshSearchableMultiselect($(this).closest("[data-searchable-multiselect]"));
  });

  $(document).on("click", function (event) {
    if (!$(event.target).closest("[data-searchable-multiselect]").length) {
      $(".searchable-multiselect").removeClass("is-open");
    }

    if (!$(event.target).closest("[data-searchable-select]").length) {
      $(".searchable-select").each(function () {
        refreshSearchableSelect(this);
      });
      $(".searchable-select").removeClass("is-open");
    }
  });

  $(document).on("click", ".rating-star", function () {
    var button = $(this);
    var root = button.closest("[data-rating-input]");
    var hidden = root.find("[data-rating-value]");
    var index = parseInt(button.data("star-index"), 10);
    var current = parseFloat(hidden.val() || 0);
    var halfValue = index - 0.5;

    hidden.val(Math.abs(current - halfValue) < 0.01 ? index : halfValue);
    renderRatingField(root);
  });

  $(document).on("click", "[data-repeater-add]", function () {
    var repeater = $(this).closest("[data-repeater]");
    var list = repeater.find("[data-repeater-list]");
    var template = repeater.find("[data-repeater-template]").html() || "";
    var nextIndex = parseInt(repeater.attr("data-next-index") || list.find("[data-repeater-item]").length, 10);
    var item = $(template.replace(/__INDEX__/g, nextIndex));

    list.append(item);
    repeater.attr("data-next-index", nextIndex + 1);
    init(item);
  });

  $(document).on("click", "[data-repeater-remove]", function () {
    $(this).closest("[data-repeater-item]").remove();
  });

  $(document).on("input", "[data-image-path-input]", function () {
    updateImagePreview(this);
  });

  $(document).on("change", "[data-image-file-input]", function () {
    updateImagePreview(this);
  });

  window.AdminFormWidgets = {
    init: init,
    updateCurrencyFieldFromDisplay: updateCurrencyFieldFromDisplay
  };

  $(document).ready(function () {
    init($(document));
  });
})(jQuery, window);
