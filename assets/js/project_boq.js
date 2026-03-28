/**
 * Project BOQ (Bill of Quantities) — quantity tracking (no pricing).
 */
(function ($) {
  "use strict";

  function projectBoqCsrfPayload(data) {
    data = data || {};
    if (typeof csrfData !== "undefined") {
      data[csrfData.token_name] = csrfData.hash;
    }
    return data;
  }

  function projectBoqProjectId() {
    return $("#project-boq-wrapper").data("project-id");
  }

  function projectBoqFormatNumber(val) {
    if (typeof accounting !== "undefined") {
      return accounting.formatNumber(
        val,
        app.options.decimal_places,
        app.options.thousand_separator,
        app.options.decimal_separator
      );
    }
    return parseFloat(val).toFixed(app.options.decimal_places);
  }

  function projectBoqUpdateRemainingDisplay($tr) {
    var revised =
      parseFloat($tr.find(".project-boq-revised-input").val()) || 0;
    var purchased = parseFloat($tr.data("purchased")) || 0;
    var rem = revised - purchased;
    $tr.find(".project-boq-remaining-display").text(projectBoqFormatNumber(rem));
  }

  function projectBoqToggleEmptyState() {
    var $empty = $("#project-boq-empty");
    if (!$empty.length) {
      return;
    }
    var has = $("#project-boq-tbody tr").length > 0;
    $empty.toggle(!has);
  }

  function projectBoqRowFromServer(row) {
    var id = row.id;
    var planned = parseFloat(row.planned_qty) || 0;
    var revised =
      row.revised_qty !== undefined && row.revised_qty !== null
        ? parseFloat(row.revised_qty)
        : planned;
    var purchased = parseFloat(row.purchased_qty) || 0;
    var desc = row.item_description || "";
    var unit = row.item_unit || "";
    var unitHtml = unit
      ? ' <span class="text-muted hide"> ' +
        $("<div>").text(unit).html() +
        "</span>"
      : "";

    var plannedCell =
      '<td class="text-right">' +
      (row.planned_qty_display || projectBoqFormatNumber(planned)) +
      "</td>";

    var revisedCell =
      '<td class="text-right"><input type="number" class="form-control input-sm text-right project-boq-revised-input" step="any" min="0" value="' +
      revised +
      '" data-boq-id="' +
      id +
      '" aria-label=""></td>';

    var actions =
      '<td>' +
      '<a href="#" class="btn btn-default btn-icon project-boq-edit" data-boq-id="' +
      id +
      '" data-item-id="' +
      row.item_id +
      '" data-planned="' +
      planned +
      '" data-revised="' +
      revised +
      '" data-toggle="modal" data-target="#project_boq_modal" title=""><i class="fa-regular fa-pen-to-square"></i></a> ' +
      '<a href="#" class="btn btn-danger btn-icon project-boq-delete" data-boq-id="' +
      id +
      '" title=""><i class="fa-regular fa-trash-can"></i></a>' +
      "</td>";

    return (
      '<tr data-boq-id="' +
      id +
      '" data-purchased="' +
      purchased +
      '" data-item-description="">' +
      '<td class="project-boq-item-label">' +
      $("<div>").text(desc).html() +
      unitHtml +
      "</td>" +
      plannedCell +
      revisedCell +
      '<td class="text-right"><input type="text" class="form-control input-sm text-right" readonly tabindex="-1" value="' +
      (row.purchased_qty_display || projectBoqFormatNumber(purchased)) +
      '"></td>' +
      '<td class="text-right"><span class="project-boq-remaining-display">' +
      (row.remaining_qty_display ||
        projectBoqFormatNumber(
          parseFloat(row.remaining_qty) || revised - purchased
        )) +
      "</span></td>" +
      ($("#project-boq-wrapper").data("can-edit") == "1" &&
      $("#project-boq-wrapper").data("can-view-items") == "1"
        ? actions
        : "") +
      "</tr>"
    );
  }

  function projectBoqRefreshRow($tr, row) {
    var $new = $(projectBoqRowFromServer(row));
    $tr.replaceWith($new);
    projectBoqToggleEmptyState();
  }

  $("body").on("input", ".project-boq-revised-input", function () {
    var $tr = $(this).closest("tr");
    projectBoqUpdateRemainingDisplay($tr);
  });

  $("body").on("blur", ".project-boq-revised-input", function () {
    var $input = $(this);
    var $tr = $input.closest("tr");
    var id = $input.data("boq-id");
    var revised = $input.val();
    var projectId = projectBoqProjectId();

    $.post(
      admin_url + "project_boq/update",
      projectBoqCsrfPayload({
        id: id,
        project_id: projectId,
        revised_qty: revised,
      })
    )
      .done(function (response) {
        try {
          response = typeof response === "string" ? JSON.parse(response) : response;
        } catch (e) {
          alert_float("danger", app.lang.something_went_wrong);
          return;
        }
        if (response.success && response.row) {
          projectBoqRefreshRow($tr, response.row);
        } else if (response.message) {
          alert_float("danger", response.message);
        }
      })
      .fail(function (data) {
        var msg =
          data.responseText && data.responseText.length < 500
            ? data.responseText
            : app.lang.something_went_wrong;
        alert_float("danger", msg);
      });
  });

  $("body").on("click", ".project-boq-delete", function (e) {
    e.preventDefault();
    if (typeof confirm_delete === "function" && !confirm_delete()) {
      return false;
    }
    var id = $(this).data("boq-id");
    var $tr = $(this).closest("tr");

    $.post(
      admin_url + "project_boq/delete",
      projectBoqCsrfPayload({
        id: id,
        project_id: projectBoqProjectId(),
      })
    )
      .done(function (response) {
        try {
          response = typeof response === "string" ? JSON.parse(response) : response;
        } catch (e) {
          alert_float("danger", app.lang.something_went_wrong);
          return;
        }
        if (response.success) {
          $tr.remove();
          projectBoqToggleEmptyState();
          alert_float("success", response.message);
        } else if (response.message) {
          alert_float("danger", response.message);
        }
      })
      .fail(function (data) {
        alert_float("danger", data.responseText || app.lang.something_went_wrong);
      });

    return false;
  });

  $("#project_boq_modal").on("show.bs.modal", function (e) {
    var btn = $(e.relatedTarget);
    if (!btn || !btn.length) {
      return;
    }
    if (btn.hasClass("project-boq-edit")) {
      $("#project-boq-modal-title").text(projectBoqLang.edit_title);
      $("#project-boq-modal-id").val(btn.data("boq-id"));
      $("#project-boq-modal-item").selectpicker(
        "val",
        btn.data("item-id").toString()
      );
      $("#project-boq-modal-planned-group").addClass("hide");
      $("#project-boq-modal-planned").removeAttr("required");
      $("#project-boq-modal-planned-readonly-group").removeClass("hide");
      $("#project-boq-modal-planned-readonly").val(btn.data("planned"));
      $("#project-boq-modal-revised-group").removeClass("hide");
      $("#project-boq-modal-revised").val(btn.data("revised"));
    } else if (btn.attr("id") === "project-boq-open-add") {
      $("#project-boq-modal-title").text(projectBoqLang.add_title);
      $("#project-boq-modal-id").val("");
      $("#project-boq-modal-item").selectpicker("val", "");
      $("#project-boq-modal-planned-group").removeClass("hide");
      $("#project-boq-modal-planned").attr("required", "required").val("0");
      $("#project-boq-modal-planned-readonly-group").addClass("hide");
      $("#project-boq-modal-revised-group").addClass("hide");
    }
  });

  $("#project-boq-modal-form").on("submit", function (e) {
    e.preventDefault();
    if ($("#project-boq-wrapper").data("can-view-items") != "1") {
      return false;
    }

    var boqId = $("#project-boq-modal-id").val();
    var itemId = $("#project-boq-modal-item").val();
    var projectId = projectBoqProjectId();

    var payload;
    var url;

    if (boqId) {
      url = admin_url + "project_boq/update";
      payload = {
        id: boqId,
        project_id: projectId,
        item_id: itemId,
        revised_qty: $("#project-boq-modal-revised").val(),
      };
    } else {
      url = admin_url + "project_boq/add";
      payload = {
        project_id: projectId,
        item_id: itemId,
        planned_qty: $("#project-boq-modal-planned").val(),
      };
    }

    $.post(url, projectBoqCsrfPayload(payload))
      .done(function (response) {
        try {
          response = typeof response === "string" ? JSON.parse(response) : response;
        } catch (e) {
          alert_float("danger", app.lang.something_went_wrong);
          return;
        }
        if (response.success && response.row) {
          if (boqId) {
            var $tr = $('tr[data-boq-id="' + boqId + '"]');
            projectBoqRefreshRow($tr, response.row);
          } else {
            $("#project-boq-tbody").append(projectBoqRowFromServer(response.row));
            projectBoqToggleEmptyState();
          }
          $("#project_boq_modal").modal("hide");
          alert_float("success", response.message);
        } else if (response.message) {
          alert_float("danger", response.message);
        }
      })
      .fail(function (data) {
        alert_float("danger", data.responseText || app.lang.something_went_wrong);
      });

    return false;
  });

  $(function () {
    $("#project-boq-tbody tr").each(function () {
      projectBoqUpdateRemainingDisplay($(this));
    });
    projectBoqToggleEmptyState();
  });
})(jQuery);
