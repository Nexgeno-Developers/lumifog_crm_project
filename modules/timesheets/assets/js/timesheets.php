<script>
  var height_window = $(window).height();
  var data_lack = <?php echo json_encode($data_lack); ?>;
  var dataObject = <?php echo json_encode($staff_row_tk); ?>;
  var dataCol = <?php echo html_entity_decode($set_col_tk); ?>;
  var dataHeader = <?php echo html_entity_decode($day_by_month_tk); ?>;
  var dataCellBackground = <?php echo json_encode($cell_background); ?>;
  var show_popup_when_cell_click = false;
  (function(){
    "use strict";


    var hotElement = document.querySelector('#example');
    var hotElementContainer = hotElement.parentNode;
    var hotSettings = { 
      data: dataObject,
      columns: dataCol,
      licenseKey: 'non-commercial-and-evaluation',
      stretchH: 'all',
      autoWrapRow: true,
      headerTooltips: true,
      minHeight:'100%',
      maxHeight:'500px',
      rowHeaders: true,
      cells: function(row, col, prop) {
        var cellProperties = {};
        if (col > 1) {
          cellProperties.renderer = firstRowRenderer;
          cellProperties.className = 'htCenter htMiddle';
        }
        return cellProperties;
      },

      width: '100%',
      rowHeights: 25,
      height:height_window-280,
      rowHeaders: true,
      colHeaders: dataHeader,
      columnSorting: {
        indicator: true
      },
      dropdownMenu: true,
      mergeCells: true,
      fixedColumnsLeft: 2,
      contextMenu: true,
      multiColumnSorting: {
        indicator: true
      },  
      hiddenColumns: {
        columns: [0],
        indicators: true
      },
      filters: true,
      afterSelection: function(r,c){
        var data = {};
        data.value = this.getValue();
        data.ColHeader = this.getColHeader(c);
        data.staffid = hot.getDataAtCell(r, 0);
        if(c > 1){
          show_detail_timesheets(data);
        }
      }
    };
    var hot = new Handsontable(hotElement, hotSettings);






    appValidateForm($('#import-timesheets-form'), {
     file_timesheets: 'required',
   })

    $('.timesheets_filter').on('click', function() {
      var data = {};
      data.month = $("#month_timesheets").val();
      data.staff = $('select[name="staff_timesheets[]"]').val();
      data.department = $('#department_timesheets').val();
      data.job_position = $('#job_position_timesheets').val();

      $.post(admin_url + 'timesheets/reload_timesheets_byfilter', data).done(function(response) {
        response = JSON.parse(response);
        dataObject = response.arr;
        dataCol = response.set_col_tk;
        dataHeader = response.day_by_month_tk;
        data_lack = response.data_lack;
        dataCellBackground = response.cell_background;
        hot.updateSettings({
          data: dataObject,
          columns: dataCol,
          colHeaders: dataHeader,
          cells: function(row, col, prop) {
            var cellProperties = {};
            if (col > 1) {
              cellProperties.renderer = firstRowRenderer;
              cellProperties.className = 'htCenter htMiddle';
            }
            return cellProperties;
          }
        })

        const month = ["01","02","03","04","05","06","07","08","09","10","11","12"];
        var d = new Date();
        var current_month = d.getMonth();
        var current_year = d.getFullYear();
        
        var current_date = current_year+'-'+month[current_month]

        if(data.month === current_date){
          show_popup_when_cell_click = false;

          var current_date_index = parseInt(d.getDate()) + 1;
          hot.selectColumns(current_date_index);

          setTimeout(function () {
            show_popup_when_cell_click = true;
          }, 800);

        }

        $('input[name="month"]').val(response.month);
        if(response.check_latch_timesheet){
          $('#btn_unlatch').removeClass('hide');
          $('#btn_latch').addClass('hide');
          $('.edit_timesheets').addClass('hide');
          $('.exit_edit_timesheets').addClass('hide');
          $('.save_time_sheet').addClass('hide');
        }else{
          $('#btn_latch').removeClass('hide');
          $('#btn_unlatch').addClass('hide');
          $('.edit_timesheets').removeClass('hide');
          $('.exit_edit_timesheets').addClass('hide');
          $('.save_time_sheet').addClass('hide');
        }
      });
    });
    $('.save_time_sheet').on('click', function() {
     $('input[name="time_sheet"]').val(JSON.stringify(hot.getData()));
   });

    $('.latch_time_sheet').on('click', function() {
     $('input[name="latch"]').val(1);
     $('input[name="time_sheet"]').val(JSON.stringify(hot.getData()));
   });

    $('.unlatch_time_sheet').on('click', function() {
     $('input[name="unlatch"]').val(1);
     $('input[name="month"]').val($("#month_timesheets").val());
   });

    $('.edit_timesheets').on('click', function() {
     $('input[name="is_edit"]').val(1);
     $('.latch_time_sheet').addClass('hide');
     $('.edit_timesheets').addClass('hide');
     $('.exit_edit_timesheets').removeClass('hide');
     $('.save_time_sheet').removeClass('hide');
   });

    $('.exit_edit_timesheets').on('click', function() {
     $('input[name="is_edit"]').val(0);
     $('.latch_time_sheet').removeClass('hide');
     $('.edit_timesheets').removeClass('hide');
     $('.exit_edit_timesheets').addClass('hide');
     $('.save_time_sheet').addClass('hide');
   });
    $('.export_excel').click(function(){
     var form = $('<form>', {
      method: 'POST',
      action: admin_url + 'timesheets/export_attendance_excel'
     });
     if (typeof csrfData !== 'undefined') {
      form.append($('<input>', { type: 'hidden', name: csrfData.token_name, value: csrfData.hash }));
     }
     form.append($('<input>', { type: 'hidden', name: 'month', value: $('input[name="month_timesheets"]').val() }));
     form.append($('<input>', { type: 'hidden', name: 'department', value: $('select[name="department_timesheets"]').val() || '' }));
     form.append($('<input>', { type: 'hidden', name: 'role', value: $('select[name="job_position_timesheets"]').val() || '' }));
     var staff = $('select[name="staff_timesheets[]"]').val() || [];
     $.each(staff, function(_, id) {
      form.append($('<input>', { type: 'hidden', name: 'staff[]', value: id }));
     });
     $('body').append(form);
     form.get(0).submit();
   });
    $(window).load(function() {
      var d = new Date();
      var month = new Array();
      month[0] = "01";
      month[1] = "02";
      month[2] = "03";
      month[3] = "04";
      month[4] = "05";
      month[5] = "06";
      month[6] = "07";
      month[7] = "08";
      month[8] = "09";
      month[9] = "10";
      month[10] = "11";
      month[11] = "12";
      $('#month_timesheets').val(d.getFullYear()+'-'+month[d.getMonth()]);

      var current_date_index = parseInt(d.getDate()) + 1;
      hot.selectColumns(current_date_index);

      setTimeout(function () {
        show_popup_when_cell_click = true;
      }, 800);
    });
  })(jQuery);


  function firstRowRenderer(instance, td, row, col, prop, value, cellProperties) {
   "use strict";
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    if(dataCellBackground != null){
      td.style.background = dataCellBackground[row][prop];
    }else{
      td.style.background = '#fff';
    }
 }
 function firstRowRenderer_2(instance, td, row, col, prop, value, cellProperties) {
   "use strict";
   Handsontable.renderers.TextRenderer.apply(this, arguments);
   td.style.fontWeight = 'bold';

 }

 function show_detail_timesheets(data){
   "use strict";
   if(show_popup_when_cell_click){
     if($('input[name="is_edit"]').val() == 0){
      var month = $("#month_timesheets").val();
      if(typeof month == 'undefined'){
        month = $('input[name="current_month"]').val();
      }
      data.month = month;
      $.post(admin_url + 'timesheets/show_detail_timesheets', data).done(function(response) {
        response = JSON.parse(response);
        $('#title_detail').html(response.title);
        $('#ul_timesheets_detail_modal').html('');
        $('#ul_timesheets_detail_modal').append(response.html);
        $('#timesheets_detail_modal').modal('show');
      });
    }
  }
}

function import_timesheets(){
  "use strict";
  $('#timesheets_detail_modal').modal('show');
}
// zaid
function view_map(latitude, longitude) {
    // Build the Google Maps Embed API URL
    const mapUrl = `https://www.google.com/maps?q=${latitude},${longitude}&hl=es;z=14&output=embed`;
    
    // Set the iframe's source to the map URL
    document.getElementById('mapIframe').src = mapUrl;

    // Show the modal
    document.getElementById('mapModal').style.display = 'flex';
}

function closeModal() {
    // Hide the modal
    document.getElementById('mapModal').style.display = 'none';
    
    // Clear the iframe source to stop the map from loading when the modal is closed
    document.getElementById('mapIframe').src = '';
}

function editCheckInOut(id, date) {
    // Set values in the modal input fields
    $("#edit_entry_id").val(id);
    $("#edit_date").val(date);

    // Show the modal
    $("#editCheckInOutModal").modal("show");
}
// zaid
</script>