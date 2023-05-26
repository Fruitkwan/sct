// GLobal validation variable
var pickUpDateSet = false;
var pickUpTimeSet = false;
var pickUpAddressSet = false;
var destAddressSet = false;
var vehicleSizeSet = true;

var month = [
  "01",
  "02",
  "03",
  "04",
  "05",
  "06",
  "07",
  "08",
  "09",
  "10",
  "11",
  "12",
];

var bookingID = [];

$(document).ready(function() {
  $("#date").change(function() {
    checkPickUpDateSet();
  });

  $("#depatureTime").change(function() {
    checkPickUpTimeSet();
  });

  $("#autocompleteDepatureAddress").change(function() {
    checkPickUpAddressSet();
  });

  $("#autocompleteDestinationAddress").change(function() {
    checkDestAddressSet();
  });

  // Check if reservation is available
  $("#reservationCheck").click(function(e) {
    // Show loading
    e.preventDefault();

    // Validate
    checkPickUpDateSet();
    checkPickUpTimeSet();
    checkPickUpAddressSet();
    checkDestAddressSet();

    if (validBookingInputs()) {
      // Get car type
      var carArray = ["small", "medium", "large"];
      var carIndex = 0;
      $(".radio-holder").each(function(i) {
        if ($(this).hasClass("selected")) {
          carIndex = i;
        }
      });

      //Set up ajx call
      $.ajaxSetup({
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
      });

      $.ajax({
        url: "book",
        method: "POST",
        data: {
          requestDate: $("#date").val(),
          depatureTime: $("#depatureTime").val(),
          departureAddress: $("#autocompleteDepatureAddress").val(),
          destinationAddress: $("#autocompleteDestinationAddress").val(),
          carType: carArray[carIndex],
          assistance: $("#assistanceInstructions").val(),
          assistanceTime_minutes: 0,
          notes: $("#notes").val(),
          returnTime: $("#returnTime").val(),
        },
        success: function(response) {
          // Hide booking option
          $("#booking-form").removeClass("active");

          // Show page based on responce
          if (response.data.status === "confirmed") {
            // Booking has been made
            // Set up the global varibab

            if (response.data.response.bookings.bookings == 2) {
              bookingID = [
                response.data.response.bookings.bookingOne.id,
                response.data.response.bookings.bookingTwo.id,
              ];
            } else {
              bookingID = [response.data.response.bookings.bookingOne.id];
            }

            console.log(response.data.response.bookings.tripType);
            $("#booking-confirm").addClass("active");

            $("#confirm-year").text(
              response.data.response.bookings.bookingOne.date
            );
            $("#confirm-time").text(
              "@ " + response.data.response.bookings.bookingOne.depatureTime
            );

            // Set up address information
            var addressFrom = response.data.response.bookings.bookingOne.departureAddress.split(
              ","
            );
            var addressTo = response.data.response.bookings.bookingOne.destinationAddress.split(
              ","
            );

            $("#confirm-type").text(
              "Trip: " + response.data.response.bookings.tripType.toUpperCase()
            );
            $("#confirm-pickup").text("From: " + addressFrom[0]);
            $("#confirm-dropoff").text("To: " + addressTo[0]);
            if (response.data.response.bookings.tripType === "two-way") {
              $("#confirm-day").text(
                (
                  response.data.response.bookings.bookingOne.cost_cents / 50
                ).toFixed(2)
              ); // Show full price since halfed

              if (response.data.response.bookings.bookings == 2) {
                $("#confirm-return").text(
                  "Return Pickup Time: " +
                    response.data.response.bookings.bookingTwo.depatureTime
                );
              } else {
                $("#confirm-return").text(
                  "Return Pickup Time: " +
                    response.data.response.bookings.bookingOne.returnTime
                );
              }
            } else {
              $("#confirm-return").text("");
              $("#confirm-day").text(
                (
                  response.data.response.bookings.bookingOne.cost_cents / 100
                ).toFixed(2)
              ); // Show full price since halfed
            }
          } else {
            // Can not accomadate
            $("#booking-error").addClass("active");
            console.log(response);
          }
          _resizeLightbox();
        },
        error: function(response) {
          console.log(response);
          $("#booking-form").removeClass("active");
          $("#booking-error").addClass("active");
          _resizeLightbox();
        },
      });
    } else {
      checkPickUpDateSet();
      checkPickUpTimeSet();
      checkPickUpAddressSet();
      checkDestAddressSet();
    }
  }); // End reservationCheck
});

function showEditBooking() {
  $("#booking-form").addClass("active");
  $("#booking-error").removeClass("active");
  _resizeLightbox();
}

function sendSpecialBooking() {
  //Set up ajx call
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
  });
  // Get car type
  var carArray = ["small", "medium", "luxury", "large"];
  var carIndex = 0;
  $(".radio-holder").each(function(i) {
    if ($(this).hasClass("selected")) {
      carIndex = i;
    }
  });
  $.ajax({
    url: "book/special",
    method: "POST",
    data: {
      requestDate: $("#date").val(),
      depatureTime: $("#depatureTime").val(),
      departureAddress: $("#autocompleteDepatureAddress").val(),
      destinationAddress: $("#autocompleteDestinationAddress").val(),
      carType: carArray[carIndex],
      assistance: $("#assistanceInstructions").val(),
      notes: $("#notes").val(),
      returnTime: $("#returnTime").val(),
    },
    success: function(response) {
      $("#booking-error").removeClass("active");
      $("#booking-success").addClass("active");
      $(".feedback-info").text(
        "We have taken your resquest and will personally contact you shortly."
      );
      _resizeLightbox();
    },
    error: function(response) {
      $("#booking-error").removeClass("active");
      $("#booking-blocked").addClass("active");
      _resizeLightbox();
    },
  });
}

function getBlankBookRideLightbox() {
  // Don't show informatin
  $("#lightbox-book-ride .lightbox-holder").addClass("notVisible");

  // Clear all entries if there were any
  var tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);

  $("#date").val(
    tomorrow.getFullYear() +
      "-" +
      twoDigitConvert(tomorrow.getMonth() + 1) +
      "-" +
      twoDigitConvert(tomorrow.getDate())
  );
  $("#depatureTime").val("09:00");
  $("#autocompleteDepatureAddress").val("");
  $("#autocompleteDestinationAddress").val("");

  // Get all selection options
  $(".radio-holder").each(function() {
    $(this).removeClass("selected");
  });

  // Remove return time
  $("#returnTime").val("");

  // Remove assitance instructions
  $("#assistanceInstructions").val("");

  // Remove notes
  $("#notes").val("");

  // Validate
  checkPickUpDateSet();
  checkPickUpTimeSet();

  // Resize
  _resizeLightbox();
  // Show lightbox
  $("#lightbox-book-ride .lightbox-holder").removeClass("notVisible");
}

function getBookRideLightbox(loc) {
  // Don't show informatin
  $("#lightbox-book-ride .lightbox-holder").addClass("notVisible");

  // Clear all entries if there were any
  var tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);

  $("#date").val(
    tomorrow.getFullYear() +
      "-" +
      twoDigitConvert(tomorrow.getMonth() + 1) +
      "-" +
      twoDigitConvert(tomorrow.getDate())
  );
  $("#depatureTime").val("09:00");

  $("#autocompleteDepatureAddress").val("");
  $("#autocompleteDestinationAddress").val(loc.google_address);

  // Get all selection options
  $(".radio-holder").each(function() {
    $(this).removeClass("selected");
  });

  // Remove return time
  $("#returnTime").val("");

  // Remove assitance instructions
  $("#assistanceInstructions").val("");

  // Remove notes
  $("#notes").val("");

  // Validate
  checkPickUpDateSet();
  checkPickUpTimeSet();
  checkDestAddressSet();

  // Resize
  _resizeLightbox();
  // Show lightbox
  $("#lightbox-book-ride .lightbox-holder").removeClass("notVisible");
}

function validBookingInputs() {
  if (
    pickUpDateSet &&
    pickUpTimeSet &&
    pickUpAddressSet &&
    destAddressSet &&
    vehicleSizeSet
  ) {
    return true;
  } else {
    return false;
  }
}

function checkPickUpDateSet() {
  // Declaration
  var date_Val = $("#date").val();

  var date1 = date_Val.split("-");
  var date2 = new Date();

  if (parseInt(date1[1], 10) > date2.getMonth() + 1) {
    $("#date").removeClass("inputInvalid");
    $("#date").addClass("inputValid");
    $("#date-error").removeClass("isVisible");
    $("#date-checkmark").addClass("isVisible");
    pickUpDateSet = true;
  } else if (parseInt(date1[1], 10) === date2.getMonth() + 1) {
    if (parseInt(date1[2], 10) > date2.getDate()) {
      $("#date").removeClass("inputInvalid");
      $("#date").addClass("inputValid");
      $("#date-error").removeClass("isVisible");
      $("#date-checkmark").addClass("isVisible");
      pickUpDateSet = true;
    } else {
      $("#date").removeClass("inputValid");
      $("#date").addClass("inputInvalid");
      $("#date-error").addClass("isVisible");
      $("#date-checkmark").removeClass("isVisible");
      $("#date-error").text("Invalide date.");
      pickUpDateSet = false;
    }
  } else if (parseInt(date1[1], 10) < date2.getMonth() + 1) {
    $("#date").removeClass("inputValid");
    $("#date").addClass("inputInvalid");
    $("#date-error").addClass("isVisible");
    $("#date-checkmark").removeClass("isVisible");
    $("#date-error").text("Invalide date.");
    pickUpDateSet = false;
  } else {
    $("#date").removeClass("inputValid");
    $("#date").addClass("inputInvalid");
    $("#date-error").addClass("isVisible");
    $("#date-checkmark").removeClass("isVisible");
    $("#date-error").text("Invalide date.");
    pickUpDateSet = false;
  }
}

function checkPickUpTimeSet() {
  // Declaration
  var time_Val = $("#depatureTime").val();

  var time_Val_array = time_Val.split(":");
  var date1 = new Date($("#date").val() + " " + $("#depatureTime").val());
  var date = new Date();

  var hours = Math.abs(date1 - date) / 36e5;

  if (hours > 12) {
    $("#depatureTime").removeClass("inputInvalid");
    $("#depatureTime").addClass("inputValid");
    $("#depatureTime-error").removeClass("isVisible");
    $("#depatureTime-checkmark").addClass("isVisible");
    pickUpTimeSet = true;
  } else {
    $("#depatureTime").removeClass("inputValid");
    $("#depatureTime").addClass("inputInvalid");
    $("#depatureTime-error").addClass("isVisible");
    $("#depatureTime-checkmark").removeClass("isVisible");
    $("#depatureTime-error").text("Bookings must be 12 hours in advance.");
    pickUpTimeSet = false;
  }
}

function checkPickUpAddressSet() {
  // Declaration
  var name_Val = $("#autocompleteDepatureAddress").val();

  // Check to see if both names are there
  if (name_Val != "") {
    $("#autocompleteDepatureAddress").removeClass("inputInvalid");
    $("#autocompleteDepatureAddress").addClass("inputValid");
    $("#autocompleteDepatureAddress-error").removeClass("isVisible");
    $("#autocompleteDepatureAddress-checkmark").addClass("isVisible");
    pickUpAddressSet = true;
  } else {
    $("#autocompleteDepatureAddress").removeClass("inputValid");
    $("#autocompleteDepatureAddress").addClass("inputInvalid");
    $("#autocompleteDepatureAddress-error").addClass("isVisible");
    $("#autocompleteDepatureAddress-checkmark").removeClass("isVisible");
    $("#autocompleteDepatureAddress-error").text("Please enter an address.");
    pickUpAddressSet = false;
  }
}

function checkDestAddressSet() {
  // Declaration
  var name_Val = $("#autocompleteDestinationAddress").val();

  // Check to see if both names are there
  if (name_Val != "") {
    $("#autocompleteDestinationAddress").removeClass("inputInvalid");
    $("#autocompleteDestinationAddress").addClass("inputValid");
    $("#autocompleteDestinationAddress-error").removeClass("isVisible");
    $("#autocompleteDestinationAddress-checkmark").addClass("isVisible");
    destAddressSet = true;
  } else {
    $("#autocompleteDestinationAddress").removeClass("inputValid");
    $("#autocompleteDestinationAddress").addClass("inputInvalid");
    $("#autocompleteDestinationAddress-error").addClass("isVisible");
    $("#autocompleteDestinationAddress-checkmark").removeClass("isVisible");
    $("#autocompleteDestinationAddress-error").text("Please enter an address.");
    destAddressSet = false;
  }
}

function acceptBooking() {
  // Reload page
  $("#booking-confirm").removeClass("active");
  // If success
  $("#booking-success").addClass("active");
  $(".feedback-info").text("Your ride has been succesfully booked.");
  _resizeLightbox();
}

function editBooking() {
  // Remove alternative
  $("#booking-confirm").removeClass("active");
  for (var i = 0; i < bookingID.length; i++) {
    //Set up ajx call
    $.ajaxSetup({
      headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
      },
    });
    var delete_url =
      window.location.href + "/booking/" + bookingID[i] + "/delete";
    console.log(delete_url);
    // Delete booking
    $.ajax({
      url: delete_url,
      method: "POST",
      data: {
        requestDate: $("#date").val(),
      },
      success: function(response) {
        console.log(response);
      },
      error: function(response) {
        console.log(response);
      },
    });
  }

  // Show booking
  $("#booking-form").addClass("active");

  // Resize
  _resizeLightbox();
}

function twoDigitConvert(number) {
  if (parseInt(number, 10) < 10) {
    number = "0" + number;
  }

  return number;
}
