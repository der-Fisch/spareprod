(function ($, window) {
  function closePublicSidebar() {
    $("body").removeClass("public-sidebar-open");
  }

  function closeAdminSidebar() {
    $("body").removeClass("admin-sidebar-open");
  }

  function currentNavbarHeight() {
    return $("[data-navbar-hide]").outerHeight() || 0;
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
      navigator.clipboard.writeText(value).then(function () {
        window.AppUi.showFlashMessage("Kode berhasil disalin.");
      }).catch(function () {
        window.AppUi.showFlashMessage("Gagal menyalin kode.", "danger");
      });
      return;
    }

    var tempInput = $("<input>");
    $("body").append(tempInput);
    tempInput.val(value);
    tempInput[0].select();
    document.execCommand("copy");
    tempInput.remove();
    window.AppUi.showFlashMessage("Kode berhasil disalin.");
  }

  function bindSidebarEvents() {
    $(document).on("click", "[data-sidebar-toggle]", function () {
      $("body").toggleClass("public-sidebar-open");
    });

    $(document).on("click", "[data-sidebar-close]", function () {
      closePublicSidebar();
    });

    $(document).on("click", "[data-admin-sidebar-toggle]", function () {
      $("body").toggleClass("admin-sidebar-open");
    });

    $(document).on("click", "[data-admin-sidebar-close]", function () {
      closeAdminSidebar();
    });

    $(window).on("resize", function () {
      if ($(window).width() > 991) {
        closePublicSidebar();
        closeAdminSidebar();
      }
    });
  }

  function bindNavbarEvents() {
    var lastScrollTop = 0;

    $(window).on("scroll", function () {
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
  }

  function bindScrollLinks() {
    $(document).on("click", "[data-scroll-target]", function (event) {
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
  }

  function bindUiModalEvents() {
    $(document).on("click", "[data-ui-modal-open]", function () {
      openUiModal($(this).data("ui-modal-open"));
    });

    $(document).on("click", "[data-ui-modal-close]", function () {
      closeUiModal();
    });

    $(document).on("click", ".ui-modal", function (event) {
      if ($(event.target).is(".ui-modal")) {
        closeUiModal();
      }
    });
  }

  function bindCopyEvents() {
    $(document).on("click", "[data-copy-text]", function () {
      copyTextValue($(this).data("copy-text"));
    });
  }

  $(document).ready(function () {
    bindSidebarEvents();
    bindNavbarEvents();
    bindScrollLinks();
    bindUiModalEvents();
    bindCopyEvents();

    if ($(".ui-modal.is-visible").length) {
      $("body").addClass("modal-open");
    }
  });
})(jQuery, window);
