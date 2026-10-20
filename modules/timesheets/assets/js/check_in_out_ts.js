(function () {
  "use strict";
  $(document).on("click", ".btn-edit-datetime", function () {
    $(this).addClass('hide');
    $('.btn-close-edit-datetime').removeClass('hide');
    $("#clock_attendance_modal .curr_date .form-group").slideDown(500);
  });
  $(document).on("click", ".btn-close-edit-datetime", function () {
    $(this).addClass('hide');
    $('.btn-edit-datetime').removeClass('hide');
    $('#clock_attendance_modal input[name="edit_date"]').val('');
    $("#clock_attendance_modal .curr_date .form-group").slideUp(500);
    var staff_id = $('#clock_attendance_modal input[name="staff_id"]').val();
    get_data_attendance(staff_id, get_date());
    $('#clock_attendance_modal input[name="edit_date"]').val('');
  });
  $(window).on('load', function () {
    var hour_attendance = $('input[name="hour_attendance"]').val();
    var minute_attendance = $('input[name="minute_attendance"]').val();
    var second_attendance = $('input[name="second_attendance"]').val();
    server_time(hour_attendance, minute_attendance, second_attendance);
    get_route_point();

    // Fast pre-fetch: if geolocation permission was previously granted, begin acquiring coordinates immediately
    var locFlag = $('input[name="enable_get_location"]').val();
    if (locFlag == '1' || locFlag == true || locFlag == 'true') {
      if (navigator.permissions && navigator.permissions.query) {
        try {
          navigator.permissions.query({ name: 'geolocation' }).then(function (result) {
            if (result.state === 'granted') {
              ts_request_location();
            }
          });
        } catch (e) {}
      }
    }
  });

  // Reset modal state if closed
  $(document).on('hidden.bs.modal', '#clock_attendance_modal', function () {
    ts_submitting = false;
    $('#clock_attendance_modal .bottom_co_btn button').each(function () {
      var orig = $(this).data('original-html');
      if (orig) {
        $(this).html(orig);
      }
      $(this).prop('disabled', false);
    });
  });
})(jQuery);
var html_progress = '<div class="text-center">';
html_progress += '<img src="'+site_url+'modules/timesheets/assets/images/loading.gif" alt="" width="100">';
html_progress += '</div>';
var run_time;

/**
 * set date
 */
function setDate(hour, minute, second) {
  "use strict";
  var secondDeg = ((second / 60) * 360) + 360;
  $("#clock_attendance_modal #secondHand").css('transform', 'rotate(' + secondDeg + 'deg)');
  var minuteDeg = ((minute / 60) * 360);
  $("#clock_attendance_modal #minuteHand").css('transform', 'rotate(' + minuteDeg + 'deg)');
  var hourDeg = ((hour / 12) * 360);
  $("#clock_attendance_modal #hourHand").css('transform', 'rotate(' + hourDeg + 'deg)');
}
/**
 * open check in out
 */
function open_check_in_out() {
  "use strict";
  var locFlag = $('input[name="enable_get_location"]').val();
  if (locFlag == '1' || locFlag == true || locFlag == 'true') {
    ts_request_location();
  }
  $("#clock_attendance_modal .curr_date .form-group").slideUp(1);
  $('#clock_attendance_modal').modal('show');
  appValidateForm($('#timesheets-form-check-in'), {
    staff_id: 'required',
    date: 'required'
  }, handle_attendance_submit);
  appValidateForm($('#timesheets-form-check-out'), {
    staff_id: 'required',
    date: 'required'
  }, handle_attendance_submit);
  $(".btn-close-edit-datetime").click();
}
/**
 * update Clock
 */
function updateClock() {
  "use strict";
  var currentTime = new Date();
  var currentHoursAP = currentTime.getHours();
  var currentHours = currentTime.getHours();
  var currentMinutes = currentTime.getMinutes();
  var currentSeconds = currentTime.getSeconds();
  currentMinutes = (currentMinutes < 10 ? "0" : "") + currentMinutes;
  currentSeconds = (currentSeconds < 10 ? "0" : "") + currentSeconds;
  var timeOfDay = (currentHours < 12) ? "AM" : "PM";
  currentHoursAP = (currentHours > 12) ? currentHours - 12 : currentHours;
  currentHoursAP = (currentHoursAP == 0) ? 12 : currentHoursAP;
  var currentTimeString = currentHours + ":" + currentMinutes + ":" + currentSeconds;
  $('.time_script').text(currentTimeString);
  $('input[name="hours"]').val(currentTimeString);
}
/**
 * change date
 */
function changedate(el) {
  "use strict";
  var date = $(el).val();
  $('#clock_attendance_modal input[name="edit_date"]').val(date);
  var staff_id = $('#clock_attendance_modal input[name="staff_id"]').val();
  get_data_attendance(staff_id, get_date());
  get_route_point();
}
/**
 * changestaff id
 */
function changestaff_id(el) {
  "use strict";
  var staff_id = $(el).val();
  $('#clock_attendance_modal input[name="staff_id"]').val(staff_id);
  get_data_attendance(staff_id, get_date());
  get_route_point();
}
/**
 * get data attendance 
 */
function get_data_attendance(staff_id, date) {
  "use strict";
  if (staff_id != '') {
    var data = {};
    data.staff_id = staff_id;
    data.date = date;
    $.post(admin_url + 'timesheets/get_data_attendance', data).done(function (response) {
      response = JSON.parse(response);
      $('#attendance_history').html('');
      $('#attendance_history').html(response.html_list);
    });
  }
}
/**
 * get date
 */
function get_date() {
  "use strict";
  var val_date = $('#clock_attendance_modal input[name="edit_date"]').val();
  if (val_date == '') {
    val_date = $('input[name="date_attendance"]').val();
  }
  return val_date;
}
/**
 * server time 
 */
function server_time(hour, minute, second) {
  "use strict";
  setDate(hour, minute, second);
  second++;
  if (second > 59) {
    second = 0;
    minute++;
    if (minute > 59) {
      minute = 0;
      hour++;
      if (hour > 23) {
        hour = 0;
      }
    }
  }
  run_time = setTimeout(function () {
    server_time(hour, minute, second);
  }, 1000)
}

function get_route_point() {
  "use strict";
  var geolocation = $('input[name="location_user"]').eq(0).val();

  if (geolocation != '' && geolocation != undefined) {
    var split = geolocation.split(',');
    var data = {};
    data.lat = split[0];
    data.lng = split[1];
    data.staff = $('select[name="staff_id"]').val();
    data.date = $('#edit_date').val();

    $.post(admin_url + 'timesheets/get_route_point_combobox', data).done(function (response) {
      response = JSON.parse(response);
      if (response.point_id == '') {
        $('.route_point_combobox').addClass('hide');
      }
      else {
        $('.route_point_combobox').removeClass('hide');
        $('select[name="route_point"]').html(response.option).selectpicker('refresh');
      }
    });
  }
}
var ts_geo_promise = null;
var ts_geo_status = 'idle'; // 'idle' | 'fetching' | 'ready' | 'error'
var ts_geo_coords = null;
var ts_geo_error = null;
var ts_submitting = false;

/**
 * Request or return user's geolocation promise
 * @param {boolean} forceFresh
 * @returns {Promise}
 */
function ts_request_location(forceFresh) {
  "use strict";
  if (!forceFresh && ts_geo_status === 'ready' && ts_geo_coords) {
    return Promise.resolve(ts_geo_coords);
  }

  if (!forceFresh && ts_geo_status === 'fetching' && ts_geo_promise) {
    return ts_geo_promise;
  }

  var $indicator = $('#ts_location_indicator');
  var $alert = $('#ts_location_alert');

  if ($indicator.length) {
    $indicator.removeClass('hide')
      .css('background-color', '#eff6ff').css('color', '#1d4ed8')
      .html('<i class="fa fa-spinner fa-spin text-info"></i> <span>Detecting location...</span>');
    $alert.addClass('hide').html('');
  }

  ts_geo_status = 'fetching';
  ts_geo_promise = new Promise(function (resolve, reject) {
    if (!navigator.geolocation) {
      ts_geo_status = 'error';
      ts_geo_error = { code: 0, message: 'Geolocation is not supported by your browser.' };
      if ($indicator.length) {
        $indicator.removeClass('hide')
          .css('background-color', '#fef2f2').css('color', '#991b1b')
          .html('<i class="fa fa-exclamation-triangle text-danger"></i> <span>Geolocation unsupported</span>');
      }
      reject(ts_geo_error);
      return;
    }

    var geoOptions = {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 60000
    };

    navigator.geolocation.getCurrentPosition(
      function (position) {
        var lat = position.coords.latitude;
        var lng = position.coords.longitude;
        ts_geo_coords = { lat: lat, lng: lng };
        ts_geo_status = 'ready';
        ts_geo_error = null;

        // Populate modal hidden coordinates
        $('#clock_attendance_modal input[name="location_user"]').val(lat + ',' + lng);

        // Update status badge
        if ($indicator.length) {
          $indicator.removeClass('hide')
            .css('background-color', '#f0fdf4').css('color', '#166534')
            .html('<i class="fa fa-check-circle text-success"></i> <span>Location ready</span>');
          $alert.addClass('hide');
        }

        try {
          sessionStorage.setItem('ts_cached_lat', lat);
          sessionStorage.setItem('ts_cached_lng', lng);
          sessionStorage.setItem('ts_cached_time', Date.now());
        } catch (e) {}

        get_route_point();
        resolve(ts_geo_coords);
      },
      function (err) {
        ts_geo_status = 'error';
        ts_geo_error = err;

        var message = 'Unable to retrieve location.';
        var advice = 'Please ensure device location is enabled.';
        if (err.code === 1) {
          message = 'Location permission denied.';
          advice = 'Please allow location permissions in your browser address bar (lock/tune icon) to take attendance.';
        } else if (err.code === 2) {
          message = 'Location signal unavailable.';
          advice = 'Please make sure your GPS / Wi-Fi location is turned on in your device settings.';
        } else if (err.code === 3) {
          message = 'Location request timed out.';
          advice = 'Securing location took too long. Please check your network and try again.';
        }

        if ($indicator.length) {
          $indicator.removeClass('hide')
            .css('background-color', '#fef2f2').css('color', '#991b1b')
            .html('<i class="fa fa-exclamation-triangle text-danger"></i> <span>' + message + '</span>');
          $alert.removeClass('hide').html('<i class="fa fa-info-circle"></i> ' + advice);
        }

        reject(err);
      },
      geoOptions
    );
  });

  return ts_geo_promise;
}

/**
 * Backward compatibility for existing calls
 */
function getLocation() {
  return ts_request_location();
}

/**
 * Intercept and handle attendance submit on the first click
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function handle_attendance_submit(form) {
  "use strict";
  if (ts_submitting) {
    return false;
  }

  var $form = $(form);
  var $btn = $form.find('button[type="submit"], .check_in, .check_out');
  var isCheckIn = ($form.attr('id') === 'timesheets-form-check-in');
  var actionText = isCheckIn ? 'Checking in...' : 'Checking out...';

  // Cache initial button HTML
  if (!$btn.data('original-html')) {
    $btn.data('original-html', $btn.html());
  }
  var originalHtml = $btn.data('original-html');

  // Check form validation
  if (typeof $form.valid === 'function') {
    if (!$form.valid()) {
      return false;
    }
  }

  var locationFlag = $('input[name="enable_get_location"]').val();
  var locationRequired = (locationFlag == '1' || locationFlag == true || locationFlag == 'true');

  function finalizeAndSubmit() {
    ts_submitting = true;
    get_data();
    $btn.html('<i class="fa fa-spinner fa-spin"></i> ' + actionText);
    $('#clock_attendance_modal .bottom_co_btn button').prop('disabled', true);
    // Submit native form DOM directly to bypass validation recursion
    form.submit();
  }

  function restoreButtons() {
    ts_submitting = false;
    $('#clock_attendance_modal .bottom_co_btn button').prop('disabled', false);
    $btn.html(originalHtml);
  }

  if (!locationRequired) {
    finalizeAndSubmit();
    return false;
  }

  // Check if location is already stored
  var currentLoc = $form.find('input[name="location_user"]').val();
  if (!currentLoc) {
    currentLoc = $('#clock_attendance_modal input[name="location_user"]').val();
  }

  if (currentLoc && currentLoc.indexOf(',') !== -1) {
    var parts = currentLoc.split(',');
    if (parts[0] && parts[1]) {
      $form.find('input[name="location_user"]').val(currentLoc);
      finalizeAndSubmit();
      return false;
    }
  }

  // Location not ready yet -> Show animated spinner on button and await location
  $btn.html('<i class="fa fa-spinner fa-spin"></i> Fetching location...');
  $('#clock_attendance_modal .bottom_co_btn button').prop('disabled', true);

  var $indicator = $('#ts_location_indicator');
  if ($indicator.length) {
    $indicator.removeClass('hide')
      .css('background-color', '#eff6ff').css('color', '#1d4ed8')
      .html('<i class="fa fa-spinner fa-spin text-info"></i> <span>Securing GPS coordinates...</span>');
  }

  ts_request_location().then(function (coords) {
    $form.find('input[name="location_user"]').val(coords.lat + ',' + coords.lng);
    $('#clock_attendance_modal input[name="location_user"]').val(coords.lat + ',' + coords.lng);
    finalizeAndSubmit();
  }).catch(function (err) {
    restoreButtons();
    var errText = 'Location access is required to take attendance. Please allow location access in your browser.';
    if (err && err.code === 1) {
      errText = 'Location permission was denied. Tap the lock icon next to the website address in your browser, allow Location access, and try again.';
    } else if (err && err.code === 2) {
      errText = 'Unable to detect your device location. Please ensure location services are enabled on your device.';
    } else if (err && err.code === 3) {
      errText = 'Location request timed out. Please check your network and try again.';
    }
    if (typeof alert_float === 'function') {
      alert_float('danger', errText);
    } else {
      alert(errText);
    }
  });

  return false;
}

function get_data() {
  "use strict";
  var route_point = $('#route_point').val();
  $('input[name="point_id"]').val(route_point);
}

/**
* open check in out
*/
function open_staff_qrcode() {
  "use strict";
  var modal_obj = $('#staff_qr_code_modal');
  modal_obj.modal('show');
  if (modal_obj.find('.modal-body').html() == '') {
    modal_obj.find('.modal-body').html(html_progress);
    $.post(admin_url + 'timesheets/get_staff_qr_code').done(function (response) {
      modal_obj.find('.modal-body').html(response);
    });
  }
}

function open_scan_qr_code() {
  $('#clock_attendance_modal').modal('hide');
  var modal_obj = $('#scan_qr_code_modal');
  modal_obj.modal('show');
  modal_obj.find('.modal-body').html(html_progress);
  $.post(admin_url + 'timesheets/get_scan_qr_code_content').done(function (response) {
    modal_obj.find('.modal-body').html(response);
    init_scan_qr();
  });
}

function init_scan_qr() {
  var html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", { fps: 10, qrbox: 250 });
  html5QrcodeScanner.render(onScanSuccess);
  $('#reader').append('<div class="scan-process-image hide">'+html_progress+'</div>');
}
var scan_session = false;
function onScanSuccess(decodedText, decodedResult) {
  clearTimeout(scan_session);
  scan_session = setTimeout(function() {
    try {
      $('#scan_qr_code_modal #result_code').addClass('hide').html('');
      $('#scan_qr_code_modal #attendance_history').html('');
      var result = `${decodedText}`;
      var split_result = result.split(':');
      var split_result2 = split_result[1].split('\n');
      if (split_result2[0] != '') {
        $('.scan-process-image').removeClass('hide');
        var user_id = split_result2[0].trim();
        $.post(admin_url + 'timesheets/checkin_from_qr_code/'+user_id).done(function (response) {
          response = JSON.parse(response);
          $('.scan-process-image').addClass('hide');
          if (response.success == true) {
              alert_float('success', response.message);
              $('#scan_qr_code_modal #result_code').removeClass('hide').html(result);
              $('#scan_qr_code_modal #attendance_history').html(response.data_attendance);
              $('#scan_qr_code_modal .scan_qr_code_continue_btn').removeClass('hide');
          }
          else{
            alert_float('danger', response.message);
          }
          return false;
        });
      }
      else{
        $('.scan-process-image').addClass('hide');
      }
    }
    catch (err) {
      $('.scan-process-image').addClass('hide');
    }
  }, 1000);
}

function scan_continue(){
  $('#scan_qr_code_modal .scan_qr_code_continue_btn').addClass('hide');
  open_scan_qr_code();
}

