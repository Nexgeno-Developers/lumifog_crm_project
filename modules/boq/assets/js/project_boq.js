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

  function projectBoqUpdateRevisedSummary() {
    var revisedCount = $("#project-boq-tbody tr.boq-row-revised").length;
    var $summary = $("#project-boq-revised-summary");
    if (!$summary.length) {
      return;
    }
    if (revisedCount > 0) {
      var textTpl = revisedCount === 1
        ? ((typeof projectBoqLang !== "undefined" && projectBoqLang.items_revised_summary) || "%s item has revised quantity")
        : ((typeof projectBoqLang !== "undefined" && projectBoqLang.items_revised_summary_plural) || "%s items have revised quantities");
      $summary.find(".boq-revised-count-text").text(textTpl.replace("%s", revisedCount));
      $summary.show().css("display", "inline-flex");
    } else {
      $summary.hide();
    }
  }

  function projectBoqRowFromServer(row) {
    var id = row.id;
    var planned = parseFloat(row.planned_qty) || 0;
    var orderQty = (row.order_quantity !== undefined && row.order_quantity !== null && parseFloat(row.order_quantity) > 0)
      ? parseFloat(row.order_quantity)
      : planned;
    var revised =
      (row._revised_for_display !== undefined && parseFloat(row._revised_for_display) !== 0)
        ? parseFloat(row._revised_for_display)
        : (row.revised_qty !== undefined && row.revised_qty !== null && parseFloat(row.revised_qty) !== 0)
          ? parseFloat(row.revised_qty)
          : orderQty;
    var purchased = parseFloat(row.purchased_qty) || 0;
    var remaining = revised - purchased;

    var isRevised = Math.abs(revised - orderQty) > 0.0001;
    var diff = revised - orderQty;
    var diffSign = diff > 0 ? "+" : "";
    var diffFormatted = diffSign + projectBoqFormatNumber(diff);
    var badgeLabel = (typeof projectBoqLang !== "undefined" && projectBoqLang.revised_badge) ? projectBoqLang.revised_badge : "Revised";
    var tooltipText = "Original: " + projectBoqFormatNumber(orderQty) + " &rarr; Revised: " + projectBoqFormatNumber(revised) + " (" + diffFormatted + ")";
    var rowClass = isRevised ? "boq-row-revised" : "";

    var intRef = $("<div>").text(row.internal_ref_no || "").html();
    var cliRef = $("<div>").text(row.client_ref_no || "").html();
    var itemId = row.item_id || "";
    var crmItemDesc = $("<div>").text(row.item_description || "").html();
    var boqDesc = $("<div>").text(row.boq_description || "").html();
    var boqUnit = $("<div>").text(row.boq_unit || "").html();

    var supplyRate = (row.supply_rate !== null && row.supply_rate !== undefined && row.supply_rate !== "") ? projectBoqFormatNumber(row.supply_rate) : "—";
    var itcRate = (row.itc_rate !== null && row.itc_rate !== undefined && row.itc_rate !== "") ? projectBoqFormatNumber(row.itc_rate) : "—";
    var supplyAmt = (row.supply_amount !== null && row.supply_amount !== undefined && row.supply_amount !== "") ? projectBoqFormatNumber(row.supply_amount) : "—";
    var itcAmt = (row.itc_amount !== null && row.itc_amount !== undefined && row.itc_amount !== "") ? projectBoqFormatNumber(row.itc_amount) : "—";
    var taxRate = (row.tax_rate !== null && row.tax_rate !== undefined && row.tax_rate !== "") ? projectBoqFormatNumber(row.tax_rate) : "—";
    var isSummary = (row.is_summary_item == "1") ? "Yes" : "No";

    var intCliRef = $("<div>").text(row.internal_client_ref_no || "").html();
    var intDesc = $("<div>").text(row.internal_description || "").html();
    var intUnit = $("<div>").text(row.internal_unit || "").html();
    var intQty = (row.internal_quantity !== null && row.internal_quantity !== undefined && row.internal_quantity !== "") ? projectBoqFormatNumber(row.internal_quantity) : "—";
    var intMatRate = (row.internal_material_rate !== null && row.internal_material_rate !== undefined && row.internal_material_rate !== "") ? projectBoqFormatNumber(row.internal_material_rate) : "—";
    var intLabRate = (row.internal_labour_rate !== null && row.internal_labour_rate !== undefined && row.internal_labour_rate !== "") ? projectBoqFormatNumber(row.internal_labour_rate) : "—";
    var intSupplyAmt = (row.internal_supply_amount !== null && row.internal_supply_amount !== undefined && row.internal_supply_amount !== "") ? projectBoqFormatNumber(row.internal_supply_amount) : "—";
    var intItcAmt = (row.internal_itc_amount !== null && row.internal_itc_amount !== undefined && row.internal_itc_amount !== "") ? projectBoqFormatNumber(row.internal_itc_amount) : "—";

    var actions =
      '<td class="text-center">' +
      '<a href="#" class="btn btn-default btn-icon project-boq-edit" data-boq-id="' +
      id +
      '" data-item-id="' +
      row.item_id +
      '" data-planned="' +
      orderQty +
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

    var itemCellHtml = '<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 4px;">' +
      '<span>' + crmItemDesc + '</span>' +
      (isRevised ? ' <span class="label label-warning boq-revised-chip" data-toggle="tooltip" data-placement="top" data-container="body" data-html="true" title="' + tooltipText + '" style="flex-shrink: 0; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d; cursor: help;"><i class="fa-solid fa-arrows-rotate tw-mr-1"></i>' + badgeLabel + '</span>' : '') +
      '</div>';

    var orderQtyDisplay = row.order_qty_display || projectBoqFormatNumber(orderQty);
    var orderQtyCellHtml = '<td class="text-right">' +
      '<div>' + orderQtyDisplay + '</div>' +
      (isRevised ? '<div style="margin-top: 3px;"><span class="label label-warning boq-revised-subbadge" data-toggle="tooltip" data-placement="top" data-container="body" data-html="true" title="' + tooltipText + '" style="font-size: 10px; font-weight: 600; padding: 2px 5px; border-radius: 3px; display: inline-block; background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d; cursor: help;"><i class="fa-solid fa-arrows-rotate tw-mr-1"></i>Rev: ' + projectBoqFormatNumber(revised) + '</span></div>' : '') +
      '</td>';

    var revisedCellClass = "text-right project-boq-revised-cell" + (isRevised ? " boq-revised-cell-highlight" : "");
    var revisedCellTooltipAttr = isRevised ? ' data-toggle="tooltip" data-placement="top" data-container="body" data-html="true" title="' + tooltipText + '"' : "";
    var revisedCellHtml = '<td class="' + revisedCellClass + '"' + revisedCellTooltipAttr + '>' +
      '<span' + (isRevised ? ' style="font-weight: 700; color: #92400e;"' : '') + '>' + (row.revised_qty_display || projectBoqFormatNumber(revised)) + '</span>' +
      (isRevised ? '<div style="font-size: 10px; font-weight: 600; color: #b45309; line-height: 1.1; margin-top: 1px;">(' + diffFormatted + ')</div>' : '') +
      '</td>';

    return (
      '<tr class="' + rowClass + '" data-boq-id="' + id + '" data-purchased="' + purchased + '" data-revised="' + revised + '" data-is-revised="' + (isRevised ? "1" : "0") + '" data-item-id="' + (row.item_id || 0) + '" data-item-description="">' +
      chkCell +
      '<td>' + intRef + '</td>' +
      '<td>' + cliRef + '</td>' +
      '<td class="text-center">' + itemId + '</td>' +
      '<td class="project-boq-item-label">' + itemCellHtml + '</td>' +
      '<td class="project-boq-desc-cell">' + boqDesc + '</td>' +
      '<td>' + boqUnit + '</td>' +
      orderQtyCellHtml +
      '<td class="text-right">' + supplyRate + '</td>' +
      '<td class="text-right">' + itcRate + '</td>' +
      '<td class="text-right">' + supplyAmt + '</td>' +
      '<td class="text-right">' + itcAmt + '</td>' +
      '<td class="text-right">' + taxRate + '</td>' +
      '<td class="text-center">' + isSummary + '</td>' +
      '<td class="boq-internal-col">' + intCliRef + '</td>' +
      '<td class="boq-internal-col project-boq-internal-desc-cell">' + intDesc + '</td>' +
      '<td class="boq-internal-col">' + intUnit + '</td>' +
      '<td class="text-right boq-internal-col">' + intQty + '</td>' +
      '<td class="text-right boq-internal-col">' + intMatRate + '</td>' +
      '<td class="text-right boq-internal-col">' + intLabRate + '</td>' +
      '<td class="text-right boq-internal-col">' + intSupplyAmt + '</td>' +
      '<td class="text-right boq-internal-col">' + intItcAmt + '</td>' +
      '<td class="text-right"><input type="text" class="form-control input-sm text-right" readonly tabindex="-1" value="' + (row.purchased_qty_display || projectBoqFormatNumber(purchased)) + '"></td>' +
      '<td class="text-right"><span class="project-boq-remaining-display">' + (row.remaining_qty_display || projectBoqFormatNumber(remaining)) + '</span></td>' +
      revisedCellHtml +
      ($("#project-boq-wrapper").data("can-edit") == "1" && $("#project-boq-wrapper").data("can-view-items") == "1" ? actions : "") +
      '</tr>'
    );
  }

  function projectBoqRefreshRow($tr, row) {
    var $new = $(projectBoqRowFromServer(row));
    $tr.replaceWith($new);
    if ($.fn.tooltip) {
      $new.find('[data-toggle="tooltip"]').tooltip({ container: "body" });
    }
    projectBoqToggleEmptyState();
    projectBoqUpdateRevisedSummary();
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
          projectBoqUpdateRevisedSummary();
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
            var $appended = $(projectBoqRowFromServer(response.row));
            $("#project-boq-tbody").append($appended);
            if ($.fn.tooltip) {
              $appended.find('[data-toggle="tooltip"]').tooltip({ container: "body" });
            }
            projectBoqToggleEmptyState();
            projectBoqUpdateRevisedSummary();
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
    var inputElem = this;
    var file = this.files[0];
    if (!file) {
      return;
    }

    var $btn = $("#project-boq-import-csv");
    var $modal = $("#project_boq_import_progress_modal");
    var $progressBar = $("#project-boq-import-progress-bar");
    var $status = $("#project-boq-import-status");
    var $hint = $("#project-boq-import-hint");
    var $filename = $("#project-boq-import-filename");

    // Format file size
    var sizeStr = "";
    if (file.size > 1048576) {
      sizeStr = (file.size / 1048576).toFixed(2) + " MB";
    } else {
      sizeStr = (file.size / 1024).toFixed(1) + " KB";
    }

    $filename.text(file.name + " (" + sizeStr + ")");
    $progressBar
      .removeClass("progress-bar-success progress-bar-danger")
      .addClass("progress-bar-primary progress-bar-striped active")
      .css("width", "0%")
      .text("0%");
    $status.text("Uploading file (0%)...");
    $hint.text("Please keep this window open while the file is processed.");
    $btn.prop("disabled", true).addClass("disabled");

    // Show modal
    if ($modal.length) {
      $modal.modal({ backdrop: "static", keyboard: false, show: true });
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
      xhr: function () {
        var xhr = new window.XMLHttpRequest();
        if (xhr.upload) {
          xhr.upload.addEventListener(
            "progress",
            function (evt) {
              if (evt.lengthComputable) {
                var percent = Math.round((evt.loaded / evt.total) * 100);
                if (percent >= 100) {
                  $progressBar.css("width", "95%").text("95%");
                  $status.html(
                    '<i class="fa fa-spinner fa-spin tw-mr-1"></i> Processing BOQ items & catalog on server...'
                  );
                  $hint.text("Spreadsheet calculations & validation in progress. Please wait...");
                } else {
                  $progressBar.css("width", percent + "%").text(percent + "%");
                  $status.text("Uploading file (" + percent + "%)...");
                }
              }
            },
            false
          );
        }
        return xhr;
      },
      success: function (response) {
        response = typeof response === "string" ? JSON.parse(response) : response;
        if (response.success) {
          $progressBar
            .removeClass("progress-bar-primary progress-bar-striped active")
            .addClass("progress-bar-success")
            .css("width", "100%")
            .text("100%");
          $status.html('<i class="fa fa-check-circle tw-mr-1 text-success"></i> Import successful!');
          $hint.text("Refreshing project page...");
          setTimeout(function () {
            if ($modal.length) {
              $modal.modal("hide");
            }
            alert_float("success", response.message);
            location.reload();
          }, 600);
        } else {
          if ($modal.length) {
            $modal.modal("hide");
          }
          $btn.prop("disabled", false).removeClass("disabled");
          alert_float("danger", response.message);
        }
      },
      error: function (xhr) {
        if ($modal.length) {
          $modal.modal("hide");
        }
        $btn.prop("disabled", false).removeClass("disabled");
        var errMsg = app.lang.something_went_wrong;
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errMsg = xhr.responseJSON.message;
        }
        alert_float("danger", errMsg);
      },
      complete: function () {
        $(inputElem).val("");
      },
    });
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

  function initProjectBoqScrollbars() {
    var $container = $(".project-boq-table-responsive");
    var $floatingScroll = $("#project-boq-floating-scroll");
    var $floatingInner = $("#project-boq-floating-scroll-inner");

    if (!$container.length || !$floatingScroll.length) {
      return;
    }

    function syncInnerWidths() {
      var sw = $container[0] ? $container[0].scrollWidth : 3090;
      if (sw < 3090) {
        sw = 3090;
      }
      $floatingInner.css("width", sw + "px");
    }

    var isSyncing = false;
    function setScroll(sl, origin) {
      if (isSyncing) return;
      isSyncing = true;
      if (origin !== "container") $container.scrollLeft(sl);
      if (origin !== "floating") $floatingScroll.scrollLeft(sl);
      requestAnimationFrame(function () {
        isSyncing = false;
      });
    }

    $container.off("scroll.boqsync").on("scroll.boqsync", function () {
      setScroll($(this).scrollLeft(), "container");
    });

    $floatingScroll.off("scroll.boqsync").on("scroll.boqsync", function () {
      setScroll($(this).scrollLeft(), "floating");
    });

    function updateFloatingVisibility() {
      if (!$container.is(":visible")) {
        $floatingScroll.hide();
        return;
      }
      syncInnerWidths();

      var rect = $container[0].getBoundingClientRect();
      var windowHeight = $(window).height();

      // Table is in vertical viewport and its native bottom scrollbar is below the screen
      var isTableInView = rect.top < windowHeight && rect.bottom > 60;
      var isBottomOffscreen = rect.bottom > windowHeight;

      if (isTableInView && isBottomOffscreen) {
        $floatingScroll.css({
          display: "block",
          left: rect.left + "px",
          width: $container.outerWidth() + "px",
        });
        $floatingScroll.scrollLeft($container.scrollLeft());
      } else {
        $floatingScroll.hide();
      }
    }

    $(window).off("scroll.boqfloat resize.boqfloat").on("scroll.boqfloat resize.boqfloat", updateFloatingVisibility);

    $("body").off("click.boqfloat").on(
      "click.boqfloat",
      "#project_boq, [data-group='project_boq'], .nav-tabs a, #side-menu-toggle",
      function () {
        setTimeout(function () {
          syncInnerWidths();
          updateFloatingVisibility();
        }, 200);
      }
    );

    setTimeout(function () {
      syncInnerWidths();
      updateFloatingVisibility();
    }, 150);
  }

  $(function () {
    if ($.fn.tooltip) {
      $("#project-boq-wrapper [data-toggle='tooltip']").tooltip({ container: "body" });
    }
    initProjectBoqScrollbars();
  });
})(jQuery);
