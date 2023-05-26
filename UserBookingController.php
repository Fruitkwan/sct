<?php

namespace App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Bookings;
use App\Models\BookingRequest;

use App\User;
use Carbon\Carbon;

use Redirect;
use Auth;

use Notification;
use App\Notifications\Admin\BookingRequestError;

class UserBookingController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware('auth:web');
  }

  public function reserve(Request $request, User $user)
  {
    // Security check

    if ($user->accountAccess()) {
      try {
        // Prepae response
        $response = new \stdClass; // Instantiate stdClass object

        // Save request
        $bookingRequest = $user->newBookingRequest($request);
        $response->bookingRequestID = $bookingRequest->id;

        // Check schedual
        $booking = $bookingRequest->getBookingReservations();
        $response->bookings = $booking;

        if ($booking->status !== "confirmed") {
          Notification::route('mail', "info@seniorcaretransportation.ca")
                       ->notify(new BookingRequestError($user->uuid,$user->name,$user->phone_number, $user->email, $request->requestDate,$request->depatureTime,$request->departureAddress,$request->destinationAddress,$request->carType,$request->assistance,$request->notes,$request->returnTime,"No cars available at the time."));

        }
        // Return new answer
        $data = array('status'=>$booking->status, 'response'=>$response);

        return response()->json([
            'data' => $data,
          ], 200);

      } catch (\Exception $e) {
        // dd($e);
        // Return error mesage if did not work
        // Email notification that there was an error
        Notification::route('mail', "info@seniorcaretransportation.ca")
                     ->notify(new BookingRequestError($user->uuid,$user->name,$user->phone_number, $user->email, $request->requestDate,$request->depatureTime,$request->departureAddress,$request->destinationAddress,$request->carType,$request->assistance,$request->notes,$request->returnTime,$e->getMessage()));

        // Return error
        $data = array('errors'=>['message'=>$e->getMessage()]);//'There was an internal error! Please try again and if persists contact us!']);
        return response()->json([
            'data' => $data,
          ], 400);
      }

    }else { // abort if something doesn't smell right
      abort(404);

    }// End security

  }

  public function reserveEmail(Request $request, User $user)
  {
    // Security check
    if ($user->accountAccess()) {
      try {
        // Prepae response
        if ($user->emailRequest($request)) {
          // Return new answer
          $data = array('status'=>'ok');
        }else {
          // Return new answer
          $data = array('status'=>'error');
        }

        return response()->json([
            'data' => $data,
          ], 200);

      } catch (\Exception $e) {
        // Return error mesage if did not work
        $data = array('errors'=>['message'=>'There was an internal error! Please try again and if persists contact us!']);
        return response()->json([
            'data' => $data,
          ], 400);
      }

    }else { // abort if something doesn't smell right
      abort(404);

    }// End security
  }

  public function delete(Request $request, User $user, Bookings $bookings)
  {
    // Booking must be done
    // Get todays date
    $today = Carbon::now();
    $reservationDate = new Carbon("$bookings->date $bookings->depatureTime");

    if ($today->diffInHours($reservationDate) > 12) {
      $bookings->status = 'cancelled';
      $bookings->save();
    }

    return redirect()->route('user.dashboard',[$user->uuid]);

  }

  public function deleteBooking(Request $request, User $user, Bookings $bookings)
  {
    $bookings->delete();
  }


  // --------------------------------
  // Debug functions
  // --------------------------------
  public function sandbox(User $user, BookingRequest $bookingrequest)
  {
    // Prepae response
    $response = new \stdClass; // Instantiate stdClass object

    // Save request
    $response->bookingRequestID = $bookingrequest->id;

    // Check schedual
    $booking = $bookingrequest->getBookingReservations();
    $response->bookings = $booking;

    // Return new answer
    $data = array('status'=>$booking->status, 'response'=>$response);

    return response()->json([
        'data' => $data,
      ], 200);

  }



}
