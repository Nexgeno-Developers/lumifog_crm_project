/**
 * Project > Expenses tab: open an expense in a side panel (like the Expenses module)
 * instead of opening the full Expenses page in a new tab.
 * Loaded by purchase_load_js() only on admin/projects/view/...?group=project_expenses.
 */
(function ($) {
  "use strict";

  var TABLE = ".table-project-expenses";
  // Columns hidden while the panel is open: name, receipt, invoice, ref #, payment mode, vendor, created by.
  // Category (1), amount (2) and date (5) stay visible. Column 0 (id) is already hidden by core.
  var HIDE_WHEN_SPLIT = [3, 4, 6, 7, 8, 9, 10];

  var built = false;
  var split = false;
  var currentId = null;

  function dt() {
    return $(TABLE).DataTable();
  }

  function buildLayout() {
    if (built) {
      return;
    }
    $("head").append(
      "<style>" +
        TABLE + " tbody tr.pur-pe-active > td { background-color: #f1f5f9 !important; }" +
        TABLE + " tbody tr.pur-pe-active > td:first-child { box-shadow: inset 3px 0 0 #2563eb; }" +
      "</style>"
    );
    var $panel = $(TABLE).closest(".panel_s");
    $panel.wrap('<div class="row" id="pur-pe-row"></div>').wrap('<div class="col-md-12" id="pur-pe-left"></div>');
    $("#pur-pe-row").append(
      '<div class="col-md-7 hide" id="pur-pe-right"><div id="expense"></div></div>'
    );

    // « / » toggle, same idea as the Expenses module
    var $btn = $(
      '<a href="#" class="btn btn-default btn-with-tooltip hidden-xs pull-right tw-ml-1.5 pur-pe-toggle hide">' +
        '<i class="fa fa-angle-double-right"></i></a>'
    );
    $btn.on("click", function (e) {
      e.preventDefault();
      if (split) {
        setSplit(false);
      } else if (currentId) {
        setSplit(true);
      }
    });
    $("#vueApp").after($btn);
    built = true;
  }

  function setSplit(on) {
    split = on;
    $("#pur-pe-left").toggleClass("col-md-5", on).toggleClass("col-md-12", !on);
    $("#pur-pe-right").toggleClass("hide", !on);
    $(".pur-pe-toggle i")
      .toggleClass("fa-angle-double-right", on)
      .toggleClass("fa-angle-double-left", !on);

    var api = dt();
    var count = api.columns().count();
    var cols = HIDE_WHEN_SPLIT.filter(function (i) {
      return i < count;
    });
    api.columns(cols).visible(!on, false);
    api.columns.adjust();
    markActiveRow();
    $(window).trigger("resize");
  }

  // Highlight the open expense only while the panel is shown.
  // (Own class, not Bootstrap "info": Perfex's theme also turns the row text blue.)
  function markActiveRow() {
    $(TABLE + " tbody tr").removeClass("pur-pe-active");
    if (!currentId || !split) {
      return;
    }
    $(TABLE + ' tbody a[href$="/expenses/list_expenses/' + currentId + '"]')
      .closest("tr")
      .addClass("pur-pe-active");
  }

  // The "Convert To Invoice" helper modal only exists on the Expenses page:
  // here, send the user there (new tab) to convert.
  function patchConvertButton() {
    if ($("#expense_convert_helper_modal").length) {
      return;
    }
    $("#expense")
      .find('[data-target="#expense_convert_helper_modal"]')
      .removeAttr("data-toggle")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        window.open(admin_url + "expenses/list_expenses/" + currentId, "_blank");
      });
  }

  function openExpense(id) {
    id = parseInt(id, 10);
    if (!id) {
      return;
    }
    buildLayout();
    currentId = id;
    if (!split) {
      setSplit(true);
    }
    $(".pur-pe-toggle").removeClass("hide");

    var $box = $("#expense");
    if (typeof destroy_dynamic_scripts_in_element === "function") {
      destroy_dynamic_scripts_in_element($box);
    }
    $box
      .html('<div class="panel_s"><div class="panel-body text-center"><i class="fa fa-spinner fa-spin"></i></div></div>')
      .load(admin_url + "expenses/get_expense_data_ajax/" + id, function () {
        patchConvertButton();
      });

    markActiveRow();

    if (typeof is_mobile === "function" && is_mobile()) {
      $("html, body").animate({ scrollTop: $box.offset().top - 20 }, 400);
    }
  }

  // Category / "View" links in the project expenses table
  $(document).on("click", TABLE + ' a[href*="/expenses/list_expenses/"]', function (e) {
    // Ctrl / Cmd / Shift / middle click keep opening a new tab
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) {
      return;
    }
    var m = String($(this).attr("href")).match(/list_expenses\/(\d+)/);
    if (!m) {
      return;
    }
    e.preventDefault();
    openExpense(m[1]);
  });

  // Keep the selected row highlighted after the table redraws (paging, sorting, search)
  $(document).on("draw.dt", TABLE, markActiveRow);

  // The preview template calls init_expense(id) after its own actions (receipt upload,
  // recurring links...). On this page there is no Expenses-page layout, so route it here.
  window.init_expense = function (id) {
    openExpense(id);
  };
})(jQuery);
