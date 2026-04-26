(function ($) {
  function csrfToken() {
    return $("meta[name='csrf-token']").attr("content");
  }

  function selectedItemIdsFrom(selector) {
    return $(selector).map(function () {
      return $(this).data("cart-item-id");
    }).get();
  }

  function updateBrandCheckboxes() {
    $("[data-brand-card]").each(function () {
      var card = $(this);
      var itemCheckboxes = card.find(".cart-item-selector");
      var checkedCount = itemCheckboxes.filter(":checked").length;
      card.find(".cart-brand-selector").prop("checked", checkedCount > 0 && checkedCount === itemCheckboxes.length);
    });
  }

  function updateSummaryUi(response) {
    $("#cart-selected-count").text(response.selected_count || 0);
    $("#cart-selected-subtotal").text(window.AppUi.formatCatalogRupiah(response.selected_subtotal));
    $("#cart-selected-tax").text(window.AppUi.formatCatalogRupiah(response.selected_tax_total));
    $("#cart-selected-total").text(window.AppUi.formatCatalogRupiah(response.selected_total));
    $("#cart-select-all").prop("checked", !!response.all_selected);
    $("#remove-selected-form button").prop("disabled", (response.selected_count || 0) < 1);
    $("#cart-checkout-button")
      .prop("disabled", (response.selected_count || 0) < 1)
      .text("Beli (" + (response.selected_count || 0) + ")");
    updateBrandCheckboxes();
  }

  function removeEmptyGroups() {
    $("[data-brand-card]").each(function () {
      if (!$(this).find("[data-cart-item-row]").length) {
        $(this).remove();
      }
    });
  }

  function showCartEmptyState() {
    $("#cart-main-content").html($("#cart-empty-state-template").html() || "");
    $(".cart-summary-panel").remove();
  }

  function postSelection(itemIds, selected) {
    return $.ajax({
      type: "POST",
      url: "/cart/selection",
      data: {
        _token: csrfToken(),
        selected: selected ? 1 : 0,
        cart_item_ids: itemIds
      },
      headers: { "X-Requested-With": "XMLHttpRequest" }
    });
  }

  function syncQuantity(form, quantityInput) {
    var cartItemId = quantityInput.data("cart-item-id");

    $.ajax({
      type: "POST",
      url: form.attr("action"),
      data: form.serialize(),
      headers: { "X-Requested-With": "XMLHttpRequest" },
      success: function (response) {
        if (response.deleted && response.removed_item_id) {
        $("#cart-item-" + response.removed_item_id).remove();
        removeEmptyGroups();
      } else {
        $("#item-line-total-" + cartItemId).text(window.AppUi.formatCatalogRupiah(response.line_total));
      }

        updateSummaryUi(response);

        if ((response.total_items || 0) === 0) {
          showCartEmptyState();
        }

        window.AppUi.showFlashMessage(response.flash_message || "Cart berhasil diperbarui.");
        window.AppUi.updateCartItemCount();
      },
      error: function () {
        window.location.reload();
      }
    });
  }

  function deleteCartItem(deleteAction, cartItemId) {
    $.ajax({
      type: "POST",
      url: deleteAction,
      data: {
        _token: csrfToken(),
        _method: "DELETE"
      },
      headers: { "X-Requested-With": "XMLHttpRequest" },
      success: function (response) {
        $("#cart-item-" + cartItemId).remove();
        removeEmptyGroups();
        updateSummaryUi(response);

        if ((response.total_items || 0) === 0) {
          showCartEmptyState();
        }

        window.AppUi.showFlashMessage(response.flash_message || "Product berhasil dihapus dari cart.");
        window.AppUi.updateCartItemCount();
      },
      error: function () {
        window.location.reload();
      }
    });
  }

  $(document).on("change", ".item-qty", function () {
    var input = $(this);
    var form = input.closest("form");

    syncQuantity(form, input);
  });

  $(document).on("click", "[data-cart-item-delete]", function () {
    var button = $(this);
    deleteCartItem(button.data("delete-action"), button.data("cart-item-id"));
  });

  $(document).on("change", ".cart-item-selector", function () {
    var checkbox = $(this);

    postSelection([checkbox.data("cart-item-id")], checkbox.is(":checked"))
      .done(function (response) {
        updateSummaryUi(response);
      });
  });

  $(document).on("change", ".cart-brand-selector", function () {
    var checkbox = $(this);
    var ids = String(checkbox.data("cart-item-ids")).split(",").filter(Boolean);

    postSelection(ids, checkbox.is(":checked"))
      .done(function (response) {
        ids.forEach(function (id) {
          $('.cart-item-selector[data-cart-item-id="' + id + '"]').prop("checked", checkbox.is(":checked"));
        });
        updateSummaryUi(response);
      });
  });

  $(document).on("change", "#cart-select-all", function () {
    var checkbox = $(this);
    var ids = selectedItemIdsFrom(".cart-item-selector");

    postSelection(ids, checkbox.is(":checked"))
      .done(function (response) {
        $(".cart-item-selector, .cart-brand-selector").prop("checked", checkbox.is(":checked"));
        updateSummaryUi(response);
      });
  });

  $(document).on("submit", "#remove-selected-form", function (event) {
    event.preventDefault();

    $.ajax({
      type: "POST",
      url: $(this).attr("action"),
      data: {
        _token: csrfToken()
      },
      headers: { "X-Requested-With": "XMLHttpRequest" },
      success: function (response) {
        $(".cart-item-selector:checked").each(function () {
          $("#cart-item-" + $(this).data("cart-item-id")).remove();
        });
        removeEmptyGroups();
        updateSummaryUi(response);

        if ((response.total_items || 0) === 0) {
          showCartEmptyState();
        }

        window.AppUi.showFlashMessage(response.flash_message || "Product terpilih berhasil dihapus dari cart.");
        window.AppUi.updateCartItemCount();
      }
    });
  });
})(jQuery);
