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
  var text = message || "Produk berhasil ditambahkan ke cart.";
  if (!window.Swal) {
    showFlashMessage(text);
    return;
  }

  Swal.fire({
    icon: "success",
    title: "Berhasil",
    text: text,
    showCancelButton: true,
    confirmButtonText: "Lihat Cart",
    cancelButtonText: "Lanjut Belanja",
    reverseButtons: true
  }).then(function(result) {
    if (result.isConfirmed) {
      window.location.href = "/cart/";
    }
  });
}

function showBackofficeSuccessAlert(message) {
  var text = message || "Perubahan berhasil disimpan.";
  if (!window.Swal) {
    showFlashMessage(text);
    return;
  }

  Swal.fire({
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
    success: function(response) {
      var badge = $("#cart-count-badge, [data-cart-count]").first();
      if (badge.length) {
        badge.text(response.count || 0);
      }
    }
  });
}

(function($) {
  var searchTimer = null;
  var lastScrollTop = 0;

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

    if (/^https?:\/\//i.test(path)) {
      return path;
    }

    if (path.charAt(0) === "/") {
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
    var digits = scale === "catalog" ? catalogValueToDigits(hiddenValue) : String(hiddenValue || "").replace(/\D/g, "");

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
    var labels = checked.map(function() {
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
      reader.onload = function(event) {
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

    stars.each(function() {
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

  function initBackofficeWidgets(root) {
    var scope = $(root);

    scope.find("[data-currency-field]").each(function() {
      syncCurrencyField(this);
    });

    scope.find("[data-searchable-select]").each(function() {
      refreshSearchableSelect(this);
    });

    scope.find("[data-searchable-multiselect]").each(function() {
      refreshSearchableMultiselect(this);
    });

    scope.find("[data-rating-input]").each(function() {
      renderRatingField(this);
    });

    scope.find("[data-repeater]").each(function() {
      var repeater = $(this);
      if (!repeater.attr("data-next-index")) {
        repeater.attr("data-next-index", repeater.find("[data-repeater-item]").length);
      }
    });

    scope.find("[data-image-path-input]").each(function() {
      updateImagePreview(this);
    });
  }

  function currentNavbarHeight() {
    return $("[data-navbar-hide]").outerHeight() || 0;
  }

  function syncHistory(url) {
    if (window.history && window.history.replaceState) {
      window.history.replaceState({}, document.title, url);
    }
  }

  function fetchHtml(url, target, onDone) {
    $.ajax({
      url: url,
      type: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      success: function(response) {
        if (response.html) {
          $(target).html(response.html);
        }
        if (onDone) {
          onDone(response);
        }
      }
    });
  }

  function closePublicSidebar() {
    $("body").removeClass("public-sidebar-open");
  }

  function closeBackofficeSidebar() {
    $("body").removeClass("backoffice-sidebar-open");
  }

  function openModal(html) {
    $("#backoffice-modal").html(html);
    $("body").addClass("modal-open");
    initBackofficeWidgets($("#backoffice-modal"));
  }

  function closeModal() {
    $("#backoffice-modal").empty();
    $("body").removeClass("modal-open");
  }

  function refreshEntityTable() {
    var shell = $("#entity-table-shell");
    if (!shell.length) {
      return;
    }
    fetchHtml(window.location.pathname + window.location.search, "#entity-table-shell");
  }

  function openUiModal(targetId) {
    $("#" + targetId).addClass("is-visible");
    $("body").addClass("modal-open");
  }

  function closeUiModal() {
    $(".ui-modal").removeClass("is-visible");
    $("body").removeClass("modal-open");
  }

  function copyTextValue(value) {
    if (!value) {
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(function() {
        showFlashMessage("Kode berhasil disalin.");
      }).catch(function() {
        showFlashMessage("Gagal menyalin kode.", "danger");
      });
      return;
    }

    var tempInput = $("<input>");
    $("body").append(tempInput);
    tempInput.val(value);
    tempInput[0].select();
    document.execCommand("copy");
    tempInput.remove();
    showFlashMessage("Kode berhasil disalin.");
  }

  $(document).on("click", "[data-sidebar-toggle]", function() {
    $("body").toggleClass("public-sidebar-open");
  });

  $(document).on("click", "[data-sidebar-close]", function() {
    closePublicSidebar();
  });

  $(document).on("click", "[data-backoffice-sidebar-toggle]", function() {
    $("body").toggleClass("backoffice-sidebar-open");
  });

  $(document).on("click", "[data-backoffice-sidebar-close]", function() {
    closeBackofficeSidebar();
  });

  $(window).on("resize", function() {
    if ($(window).width() > 991) {
      closePublicSidebar();
      closeBackofficeSidebar();
    }
  });

  $(window).on("scroll", function() {
    var navbar = $("[data-navbar-hide]");
    if (!navbar.length || $("body").hasClass("public-sidebar-open")) {
      return;
    }

    var currentTop = $(window).scrollTop();
    if (currentTop > 120 && currentTop > lastScrollTop) {
      navbar.addClass("is-hidden");
    } else {
      navbar.removeClass("is-hidden");
    }
    lastScrollTop = currentTop;
  });

  $(document).on("click", "[data-scroll-target]", function(event) {
    var targetId = $(this).data("scroll-target");
    var target = $("#" + targetId);
    if (!target.length) {
      return;
    }

    event.preventDefault();
    $("html, body").animate({
      scrollTop: target.offset().top - currentNavbarHeight() - 18
    }, 500);
  });

  $(document).on("click", "[data-ui-modal-open]", function() {
    openUiModal($(this).data("ui-modal-open"));
  });

  $(document).on("click", "[data-ui-modal-close]", function() {
    closeUiModal();
  });

  $(document).on("click", "[data-copy-text]", function() {
    copyTextValue($(this).data("copy-text"));
  });

  $(document).on("click", ".ui-modal", function(event) {
    if ($(event.target).is(".ui-modal")) {
      closeUiModal();
    }
  });

  if ($(".ui-modal.is-visible").length) {
    $("body").addClass("modal-open");
  }

  $(document).on("submit", "[data-live-search-form]", function(event) {
    var form = $(this);
    var target = form.data("results-target");
    if (!target) {
      return;
    }

    event.preventDefault();
    var url = form.attr("action") + "?" + form.serialize();
    fetchHtml(url, target, function() {
      syncHistory(url);
    });
  });

  $(document).on("input", "[data-live-search-form] input[name='q']", function() {
    var form = $(this).closest("form");
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(function() {
      form.trigger("submit");
    }, 250);
  });

  $(document).on("input", "[data-price-format]", function() {
    var digits = ($(this).val() || "").replace(/\D/g, "");
    if (!digits) {
      $(this).val("");
      return;
    }
    $(this).val(digits.replace(/\B(?=(\d{3})+(?!\d))/g, "."));
  });

  $(document).on("submit", ".catalog-filter-form", function() {
    $(this).find("[data-price-format]").each(function() {
      $(this).val(($(this).val() || "").replace(/\D/g, ""));
    });
  });

  $(document).on("click", "[data-catalog-page]", function(event) {
    event.preventDefault();
    var url = $(this).attr("href");
    fetchHtml(url, "#catalog-results", function() {
      syncHistory(url);
    });
  });

  $(document).on("submit", "[data-entity-search]", function(event) {
    var form = $(this);
    var baseUrl = window.location.pathname;
    var query = form.serialize();
    event.preventDefault();
    fetchHtml(baseUrl + "?" + query, "#entity-table-shell", function() {
      syncHistory(baseUrl + "?" + query);
    });
  });

  $(document).on("click", "[data-entity-page]", function(event) {
    event.preventDefault();
    var url = $(this).attr("href");
    fetchHtml(url, "#entity-table-shell", function() {
      syncHistory(url);
    });
  });

  $(document).on("click", "[data-modal-open]", function(event) {
    event.preventDefault();
    $.ajax({
      url: $(this).attr("href"),
      type: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      success: function(response) {
        if (response.html) {
          openModal(response.html);
        }
      }
    });
  });

  $(document).on("click", "[data-modal-close]", function(event) {
    event.preventDefault();
    closeModal();
  });

  $(document).on("click", ".backoffice-modal", function(event) {
    if ($(event.target).is(".backoffice-modal")) {
      closeModal();
    }
  });

  $(document).on("input change", "[data-currency-display]", function() {
    updateCurrencyFieldFromDisplay(this);
  });

  $(document).on("focus click", "[data-searchable-select-input]", function(event) {
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

  $(document).on("input", "[data-searchable-select-input]", function() {
    var wrapper = $(this).closest("[data-searchable-select]");
    var term = ($(this).val() || "").toLowerCase();
    wrapper.addClass("is-open");
    wrapper.find("[data-searchable-select-option]").each(function() {
      var option = $(this);
      option.toggle((option.data("option-label") || "").indexOf(term) !== -1);
    });
  });

  $(document).on("click", "[data-searchable-select-option]", function() {
    var option = $(this);
    var wrapper = option.closest("[data-searchable-select]");

    wrapper.find("[data-searchable-select-value]").val(option.data("option-value"));
    refreshSearchableSelect(wrapper);
    wrapper.find("[data-searchable-select-input]").trigger("blur");
    wrapper.removeClass("is-open");
  });

  $(document).on("click", "[data-multiselect-trigger]", function(event) {
    event.preventDefault();
    var wrapper = $(this).closest("[data-searchable-multiselect]");
    $(".searchable-select").removeClass("is-open");
    $(".searchable-multiselect").not(wrapper).removeClass("is-open");
    wrapper.toggleClass("is-open");
  });

  $(document).on("input", "[data-multiselect-search]", function() {
    var term = ($(this).val() || "").toLowerCase();
    $(this).closest("[data-searchable-multiselect]").find("[data-option-label]").each(function() {
      var option = $(this);
      option.toggle(option.data("option-label").indexOf(term) !== -1);
    });
  });

  $(document).on("change", "[data-searchable-multiselect] input[type='checkbox']", function() {
    refreshSearchableMultiselect($(this).closest("[data-searchable-multiselect]"));
  });

  $(document).on("click", function(event) {
    if (!$(event.target).closest("[data-searchable-multiselect]").length) {
      $(".searchable-multiselect").removeClass("is-open");
    }
    if (!$(event.target).closest("[data-searchable-select]").length) {
      $(".searchable-select").each(function() {
        refreshSearchableSelect(this);
      });
      $(".searchable-select").removeClass("is-open");
    }
  });

  $(document).on("click", ".rating-star", function() {
    var button = $(this);
    var root = button.closest("[data-rating-input]");
    var hidden = root.find("[data-rating-value]");
    var index = parseInt(button.data("star-index"), 10);
    var current = parseFloat(hidden.val() || 0);
    var halfValue = index - 0.5;

    if (Math.abs(current - halfValue) < 0.01) {
      hidden.val(index);
    } else {
      hidden.val(halfValue);
    }

    renderRatingField(root);
  });

  $(document).on("click", "[data-repeater-add]", function() {
    var repeater = $(this).closest("[data-repeater]");
    var list = repeater.find("[data-repeater-list]");
    var template = repeater.find("[data-repeater-template]").html() || "";
    var nextIndex = parseInt(repeater.attr("data-next-index") || list.find("[data-repeater-item]").length, 10);
    var html = template.replace(/__INDEX__/g, nextIndex);
    var item = $(html);

    list.append(item);
    repeater.attr("data-next-index", nextIndex + 1);
    initBackofficeWidgets(item);
  });

  $(document).on("click", "[data-repeater-remove]", function() {
    $(this).closest("[data-repeater-item]").remove();
  });

  $(document).on("input", "[data-image-path-input]", function() {
    updateImagePreview(this);
  });

  $(document).on("change", "[data-image-file-input]", function() {
    updateImagePreview(this);
  });

  $(document).ready(function() {
    $("[data-price-format]").each(function() {
      var digits = ($(this).val() || "").replace(/\D/g, "");
      if (digits) {
        $(this).val(digits.replace(/\B(?=(\d{3})+(?!\d))/g, "."));
      }
    });

    if ($("#cart-count-badge, [data-cart-count]").length) {
      updateCartItemCount();
    }

    initBackofficeWidgets($(document));
  });

  $(document).on("submit", "[data-modal-form]", function(event) {
    var form = $(this);
    event.preventDefault();

    form.find("[data-currency-display]").each(function() {
      updateCurrencyFieldFromDisplay(this);
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
      success: function(response) {
        if (response.html) {
          openModal(response.html);
          return;
        }
        closeModal();
        refreshEntityTable();
        if (response.message) {
          showBackofficeSuccessAlert(response.message);
        }
      },
      error: function(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.html) {
          openModal(xhr.responseJSON.html);
          return;
        }
        showFlashMessage("Terjadi kesalahan saat memproses data.", "danger");
      }
    });
  });
})(jQuery);
