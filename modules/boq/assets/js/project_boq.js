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
    var revised = parseFloat($tr.attr("data-revised")) || 0;
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
      (row._revised_for_display !== undefined && parseFloat(row._revised_for_display) !== 0)
        ? parseFloat(row._revised_for_display)
        : (row.revised_qty !== undefined && row.revised_qty !== null && parseFloat(row.revised_qty) !== 0)
          ? parseFloat(row.revised_qty)
          : (row.order_quantity !== undefined && row.order_quantity !== null ? parseFloat(row.order_quantity) : planned);
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
      '<td class="text-right">' +
      projectBoqFormatNumber(revised) +
      "</td>";

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

    var chkCell =
      $("#project-boq-wrapper").data("can-view-items") == "1"
        ? '<td class="text-center project-boq-select-cell"><input type="checkbox" class="project-boq-select" aria-label=""></td>'
        : "";

    return (
      '<tr data-boq-id="' +
      id +
      '" data-purchased="' +
      purchased +
      '" data-revised="' +
      revised +
      '" data-item-id="' +
      (row.item_id || 0) +
      '" data-item-description="">' +
      chkCell +
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

  $("#project-boq-check-all").on("change", function () {
    var on = $(this).prop("checked");
    $("#project-boq-tbody .project-boq-select").prop("checked", on);
  });

  $(document).on("change", "#project-boq-tbody .project-boq-select", function () {
    var $rows = $("#project-boq-tbody .project-boq-select");
    if (!$rows.length) {
      return;
    }
    var all = $rows.filter(":checked").length === $rows.length;
    $("#project-boq-check-all").prop("checked", all);
  });

  $("#project-boq-purchase-request").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var items = [];
    $("#project-boq-tbody tr").each(function () {
      var $tr = $(this);
      if (!$tr.find(".project-boq-select").prop("checked")) {
        return;
      }
      var itemId = parseInt($tr.data("item-id"), 10);
      var boqItemId = parseInt($tr.data("boq-id"), 10);
      var revised = parseFloat($tr.attr("data-revised")) || 0;
      var purchased = parseFloat($tr.data("purchased")) || 0;
      var remaining = revised - purchased;
      if (remaining < 0) {
        remaining = 0;
      }
      items.push({
        item_id: itemId,
        remaining_qty: remaining,
        boq_item_id: boqItemId,
      });
    });

    if (!items.length) {
      alert_float("warning", projectBoqLang.select_at_least_one);
      return;
    }

    var projectId = projectBoqProjectId();
    var qs =
      "boq=1&project_id=" +
      encodeURIComponent(projectId) +
      "&items=" +
      encodeURIComponent(JSON.stringify(items));
    var targetUrl = admin_url + "purchase/pur_request?" + qs;
    var link = document.createElement("a");
    link.href = targetUrl;
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.style.display = "none";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });

  $(function () {
    $("#project-boq-tbody tr").each(function () {
      projectBoqUpdateRemainingDisplay($(this));
    });
    projectBoqToggleEmptyState();
  });

  $("#project-boq-import-csv").on("click", function () {
    $("#project-boq-csv-file").click();
  });

  $("#project-boq-csv-file").on("change", function () {
    var file = this.files[0];
    if (!file) {
      return;
    }
    var formData = new FormData();
    formData.append("boq_csv", file);
    if (typeof csrfData !== "undefined") {
      formData.append(csrfData.token_name, csrfData.hash);
    }
    var projectId = projectBoqProjectId();
    $.ajax({
      url: admin_url + "project_boq/import_csv/" + projectId,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        "X-CSRF-Token": typeof csrfData !== "undefined" ? csrfData.hash : undefined,
        "X-Requested-With": "XMLHttpRequest",
      },
      success: function (response) {
        response = typeof response === "string" ? JSON.parse(response) : response;
        if (response.success) {
          alert_float("success", response.message);
          location.reload();
        } else {
          alert_float("danger", response.message);
        }
      },
      error: function () {
        alert_float("danger", app.lang.something_went_wrong);
      },
    });
    $(this).val("");
  });

  $("body").on("click", ".boq-approve-request", function () {
    var requestId = $(this).data("request-id");
    $.post(
      admin_url + "project_boq/process_edit_request",
      projectBoqCsrfPayload({
        request_id: requestId,
        status: "approved",
        review_notes: "",
      })
    )
      .done(function (response) {
        response = typeof response === "string" ? JSON.parse(response) : response;
        if (response.success) {
          alert_float("success", response.message);
          location.reload();
        } else {
          alert_float("danger", response.message);
        }
      })
      .fail(function (data) {
        alert_float("danger", data.responseText || app.lang.something_went_wrong);
      });
  });

  $("body").on("click", ".boq-reject-request", function () {
    var requestId = $(this).data("request-id");
    var notes = prompt(projectBoqLang.review_notes || "Review notes (optional)", "");
    if (notes === null) {
      return;
    }
    $.post(
      admin_url + "project_boq/process_edit_request",
      projectBoqCsrfPayload({
        request_id: requestId,
        status: "rejected",
        review_notes: notes,
      })
    )
      .done(function (response) {
        response = typeof response === "string" ? JSON.parse(response) : response;
        if (response.success) {
          alert_float("success", response.message);
          location.reload();
        } else {
          alert_float("danger", response.message);
        }
      })
      .fail(function (data) {
        alert_float("danger", data.responseText || app.lang.something_went_wrong);
      });
  });

  $("body").on("click", ".project-boq-request-edit", function () {
    var btn = $(this);
    var boqId = btn.data("boq-id");
    var itemId = btn.data("item-id");
    var planned = parseFloat(btn.data("planned")) || 0;
    var revised = parseFloat(btn.data("revised")) || 0;

    $("#project-boq-request-type").val("change_qty");
    $("#project-boq-request-boq-id").val(boqId);
    $("#project-boq-request-modal-title").text(projectBoqLang.request_edit || "Request Edit");
    $("#project-boq-request-add-fields").addClass("hide");
    $("#project-boq-request-edit-fields").removeClass("hide");
    $("#project-boq-request-edit-item-name").val("Item #" + itemId);
    $("#project-boq-request-edit-planned").val(projectBoqFormatNumber(planned));
    $("#project-boq-request-edit-revised").val(revised);
    $("#project_boq_request_modal").modal("show");
  });

  $("#project-boq-open-add").on("click", function () {
    $("#project-boq-request-type").val("add_item");
    $("#project-boq-request-boq-id").val("");
    $("#project-boq-request-modal-title").text(projectBoqLang.request_add_item || "Request Add BOQ Line");
    $("#project-boq-request-add-fields").removeClass("hide");
    $("#project-boq-request-edit-fields").addClass("hide");
    $("#project-boq-request-form")[0].reset();
  });

  $("#project-boq-request-form").on("submit", function (e) {
    e.preventDefault();
    var projectId = projectBoqProjectId();
    var requestType = $("#project-boq-request-type").val();
    var payload = {
      project_id: projectId,
      request_type: requestType,
    };

    if (requestType === "add_item") {
      payload.item_id = $("#project-boq-request-item-id").val();
      payload.order_quantity = $("#project-boq-request-planned-qty").val();
      payload.boq_description = $("#project-boq-request-description").val();
      payload.boq_unit = $("#project-boq-request-unit").val();
      payload.supply_rate = $("#project-boq-request-supply-rate").val();
      payload.itc_rate = $("#project-boq-request-itc-rate").val();
      payload.supply_amount = $("#project-boq-request-supply-amount").val();
      payload.itc_amount = $("#project-boq-request-itc-amount").val();
      payload.tax_rate = $("#project-boq-request-tax-rate").val();
      payload.is_summary_item = $("#project-boq-request-summary-item").val();
      payload.internal_ref_no = $("#project-boq-request-internal-ref-no").val();
      payload.client_ref_no = $("#project-boq-request-client-ref-no").val();
      payload.internal_client_ref_no = $("#project-boq-request-int-client-ref").val();
      payload.internal_description = $("#project-boq-request-int-desc").val();
      payload.internal_unit = $("#project-boq-request-int-unit").val();
      payload.internal_quantity = $("#project-boq-request-int-qty").val();
      payload.internal_material_rate = $("#project-boq-request-int-mat-rate").val();
      payload.internal_labour_rate = $("#project-boq-request-int-lab-rate").val();
      payload.internal_supply_amount = $("#project-boq-request-int-supply-amt").val();
      payload.internal_itc_amount = $("#project-boq-request-int-itc-amt").val();
    } else {
      payload.boq_item_id = $("#project-boq-request-boq-id").val();
      payload.order_quantity = $("#project-boq-request-edit-revised").val();
    }

    $.post(
      admin_url + "project_boq/request_edit",
      projectBoqCsrfPayload(payload)
    )
      .done(function (response) {
        response = typeof response === "string" ? JSON.parse(response) : response;
        if (response.success) {
          alert_float("success", response.message);
          $("#project_boq_request_modal").modal("hide");
        } else {
          alert_float("danger", response.message);
        }
      })
      .fail(function (data) {
        alert_float("danger", data.responseText || app.lang.something_went_wrong);
      });
  });
})(jQuery);
