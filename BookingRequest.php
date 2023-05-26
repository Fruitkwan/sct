<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Notification;
use App\Notifications\Admin\BookingRequestConfirm;

class BookingRequest extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'user_uuid','requestDate','depatureTime','departureAddress','destinationAddress','carType','assistance','assistanceTime_minutes','notes','returnTime','confirmed'
  ];



  public function getBookingReservations()
  {
    // Get distance and duration
    $tripData = $this->googleAPI();

    // Design responces
    $response = new \stdClass;


    // If there is no data, there is an error and must ne a custom solution
    if ($tripData->status === 'ZERO_RESULTS') {
      $response->status = 'error';

    }else {
      // Determin assitance
      if (isset($this->assistance)) {
        if ($tripData->duration->value < 3600) {
          $this->assistanceTime_minutes = 60;
          $this->save();
        }else {
          $this->assistanceTime_minutes = $tripData->duration->value * 60;
          $this->save();
        }
      }
      // Trip case
      $tripType = 'one-way';

      // If a bus, costome email
      if ($this->carType === "large") {
        $tripType = 'custom';

      }else if (isset($this->returnTime)) {
        $tripType = 'two-way';

      }else {
        $tripType = 'one-way';

      }

      // Return trip type
      $response->tripType = $tripType;

      // Switch case to get responce
      switch ($tripType) {
        case 'custom':
          // Return custom response
          $response->status = 'custom';
          break;

        case 'two-way':
          // Determine time in between
          $timeInBetween = strtotime($this->returnTime) - (strtotime($this->depatureTime) + $tripData->duration->value + ($this->assistanceTime_minutes * 60));

          if ( $timeInBetween <= (60*60)) { // 60 minutes in seconds
            // Single booking
            $vehicle = $this->getRoundTripVehicle($tripData);

            // If available, make booking
            // if ($vehicle->vehicleOne === 'available') { // Car is available so make the booking
            //
            if ($vehicle->status === 'available') { // Car is available so make the booking
              if ($vehicle->vehicleOne === 'available') { // Car is available so make the booking
                // Determine the cost
                $cost = $this->tripCost($tripData->duration->value,$tripData->distance->value, $this->assistanceTime_minutes, true);

                // Total booking
                $duration = ($tripData->duration->value * 2 ) + (strtotime($this->returnTime) - strtotime($this->depatureTime));

                // Create booking
                $booking = Bookings::create([
                  'user_uuid' => $this->user_uuid,
                  'car_uuid' => $vehicle->vehicleOneInfo->uuid,
                  'status' => 'requested', // still has to be validated by admin
                  'date' => $this->requestDate,
                  'depatureTime' => $this->depatureTime,
                  'departureAddress' => $this->departureAddress,
                  'destinationAddress' => $this->destinationAddress,
                  'assistance' => $this->assistance,
                  'assistanceTime_minutes' => $this->assistanceTime_minutes,
                  'notes' => $this->notes,
                  'returnTime' => $this->returnTime,
                  'distance' => $tripData->distance->text,
                  'distance_meters' => $tripData->distance->value * 2,
                  'duration' => $tripData->duration->text,
                  'duration_seconds' => $duration,
                  'cost_cents' => $cost,
                  'confirmation' => true, // Auto confirmed since available
                ]);

                // Set return status
                $response->status = 'confirmed';
                $response->bookings = 1;
                $response->bookingOne = $booking;
              }

            } else { // No cars are available
              $response->status = 'unavailable';

            }


          }else {
            // Attempte to book both trips
            $vehicles = $this->getRoundTripVehicles($tripData);

            // If available, make booking
            if ($vehicles->status === 'available') { // Car is available so make the booking
              // Determine the cost
              $cost = $this->tripCost($tripData->duration->value,$tripData->distance->value, $this->assistanceTime_minutes, true);

              // First duration
              $duration = $tripData->duration->value + ($this->assistanceTime_minutes * 60);

              // Create booking 1
              $bookingOne = Bookings::create([
                'user_uuid' => $this->user_uuid,
                'car_uuid' => $vehicles->vehicleOneInfo->uuid,
                'status' => 'requested', // still has to be validated by admin
                'date' => $this->requestDate,
                'depatureTime' => $this->depatureTime,
                'departureAddress' => $this->departureAddress,
                'destinationAddress' => $this->destinationAddress,
                'assistance' => $this->assistance,
                'assistanceTime_minutes' => $this->assistanceTime_minutes,
                'notes' => $this->notes,
                'distance' => $tripData->distance->text,
                'distance_meters' => $tripData->distance->value,
                'duration' => $tripData->duration->text,
                'duration_seconds' => $duration,
                'cost_cents' => $cost/2,
                'confirmation' => true, // Auto confirmed since available
              ]);

              // Create booking 2
              $bookingTwo = Bookings::create([
                'user_uuid' => $this->user_uuid,
                'car_uuid' => $vehicles->vehicleTwoInfo->uuid,
                'status' => 'requested', // still has to be validated by admin
                'date' => $this->requestDate,
                'depatureTime' => $this->returnTime,
                'departureAddress' => $this->destinationAddress, // switched for the return
                'destinationAddress' => $this->departureAddress,
                'notes' => $this->notes,
                'distance' => $tripData->distance->text,
                'distance_meters' => $tripData->distance->value,
                'duration' => $tripData->duration->text,
                'duration_seconds' => $tripData->duration->value,
                'cost_cents' => $cost/2,
                'confirmation' => true, // Auto confirmed since available
              ]);

              // Set return status
              $response->status = 'confirmed';
              $response->bookings = 2;
              $response->bookingOne = $bookingOne;
              $response->bookingTwo = $bookingTwo;


            } else { // No cars are available
              $response->status = 'unavailable';

            }

          }

          break;

        default:
          // One way trip
          $vehicle = $this->getOneWayVehicle($tripData);

          // If available, make booking
          if ($vehicle->status === 'available') { // Car is available so make the booking
            // Determine the cost
            $cost = $this->tripCost($tripData->duration->value, $tripData->distance->value, $this->assistanceTime_minutes, false);

            // Create booking
            $booking = Bookings::create([
              'user_uuid' => $this->user_uuid,
              'car_uuid' => $vehicle->vehicleOneInfo->uuid,
              'status' => 'requested',
              'date' => $this->requestDate,
              'depatureTime' => $this->depatureTime,
              'departureAddress' => $this->departureAddress,
              'destinationAddress' => $this->destinationAddress,
              'assistance' => $this->assistance,
              'assistanceTime_minutes' => $this->assistanceTime_minutes,
              'notes' => $this->notes,
              'distance' => $tripData->distance->text,
              'distance_meters' => $tripData->distance->value,
              'duration' => $tripData->duration->text,
              'duration_seconds' => $tripData->duration->value,
              'cost_cents' => $cost,
              'confirmation' => true,
            ]);

            // Set return status
            $response->status = 'confirmed';
            $response->bookings = 1;
            $response->bookingOne = $booking;

          }else { // No cars are available
            $response->status = 'unavailable';

          }

          break;

      }


    }

    // Email confimation
    if ($response->status === 'confirmed' && isset($booking)) {

      $booking->getUsersInfo();

      Notification::route('mail', "info@seniorcaretransportation.ca")
                    ->notify(new BookingRequestConfirm(
                      $booking->user->name,
                      $booking->user->phone,
                      $booking->user->email,
                      $this->requestDate,
                      $this->depatureTime,
                      $this->departureAddress,
                      $this->destinationAddress,
                      $this->carType,
                      $this->assistance,
                      $this->notes,
                      $this->returnTime,
                      $booking->cost_cents));


    }

    // Return booking
    return $response;
  }

  public function getRoundTripVehicles($tripData)
  {
    // Set response variable
    $response = new \stdClass;
    $response->status = 'unavailable'; // default
    $response->vehicleOne = 'unavailable';
    $response->vehicleTwo = 'unavailable';

    // Get vehicle type
    $vehicleType = VehicleType::where('type',$this->carType)->first();

    // Trip duration
    $duration = $tripData->duration->value + ($this->assistanceTime_minutes * 60);

    // Get first vehicle
    foreach (Vehicles::where('active',true)->where('vehicle_type',$vehicleType->id)->get() as $key => $vehicle) {
      // Get car's current booking
      if ($vehicle->available($this->requestDate,$this->depatureTime,$duration)) {
        // Return car information
        $response->vehicleOne = 'available';
        $response->vehicleOneInfo = $vehicle;

        // Leave since availabl
        break;

      }// end if

    } // end foreach loop

    // Find backup if need be
    if (($response->vehicleOne != 'available') && ($vehicleType->id === 1)) {
      // Get a list of all the available cars
      foreach (Vehicles::where('active',true)->where('vehicle_type',2)->get() as $key => $vehicle) {
        // Get car's current booking
        if ($vehicle->available($this->requestDate,$this->depatureTime,$duration)) {
          // Return car information
          $response->vehicleOne = 'available';
          $response->vehicleOneInfo = $vehicle;

          // Leave since availabl
          break;

        }// end if

      } // end foreach loop

    }


    // Get second car
    foreach (Vehicles::where('active',true)->where('vehicle_type',$vehicleType->id)->get() as $key => $vehicle) {
      // Get car's current booking
      if ($vehicle->available($this->requestDate,$this->returnTime,$tripData->duration->value)) {
        // Return car information
        $response->vehicleTwo = 'available';
        $response->vehicleTwoInfo = $vehicle;

        // Leave since availabl
        break;

      }// end if

    } // end foreach loop

    // Find backup if need be
    if (($response->vehicleTwo != 'available') && ($vehicleType->id === 1)) {
      // Get a list of all the available cars
      foreach (Vehicles::where('active',true)->where('vehicle_type',2)->get() as $key => $vehicle) {
        // Get car's current booking
        if ($vehicle->available($this->requestDate,$this->depatureTime,$tripData->duration->value)) {
          // Return car information
          $response->vehicleTwo = 'available';
          $response->vehicleTwoInfo = $vehicle;

          // Leave since availabl
          break;

        }// end if

      } // end foreach loop

    }

    // Design output
    if ($response->vehicleOne != 'unavailable' && $response->vehicleTwo != 'unavailable') {
      $response->status = 'available';
    }

    return $response;

  }

  public function getRoundTripVehicle($tripData)
  {
    // Set response variable
    $response = new \stdClass;
    $response->status = 'unavailable'; // default

    // Get vehicle type
    $vehicleType = VehicleType::where('type',$this->carType)->first();

    // Get a list of all the available cars
    foreach (Vehicles::where('active',true)->where('vehicle_type',$vehicleType->id)->get() as $key => $vehicle) {
      // Get car's current booking
      // Duration -> there back and time in between
      $duration = ($tripData->duration->value * 2 ) + (strtotime($this->returnTime) - strtotime($this->depatureTime));

      if ($vehicle->available($this->requestDate,$this->depatureTime,$duration)) {
        // Return car information
        $response->status = 'available'; // default
        $response->vehicleOne = 'available';
        $response->vehicleOneInfo = $vehicle;

        // Leave since availabl
        break;

      }// end if

    } // end foreach loop

    // Find backup if need be
    if (($response->status != 'available') && ($vehicleType->id === 1)) {
      // Get a list of all the available cars
      foreach (Vehicles::where('active',true)->where('vehicle_type',2)->get() as $key => $vehicle) {
        // Get car's current booking
        if ($vehicle->available($this->requestDate,$this->depatureTime,$duration)) {
          // Return car information
          $response->status = 'available'; // default
          $response->vehicleOne = 'available';
          $response->vehicleOneInfo = $vehicle;

          // Leave since availabl
          break;

        }// end if

      } // end foreach loop

    }

    // Return
    return $response;
  }
  public function getOneWayVehicle($tripData)
  {
    // Set response variable
    $response = new \stdClass;
    $response->status = 'unavailable'; // default

    // Get vehicle type
    $vehicleType = VehicleType::where('type',$this->carType)->first();

    // Include assistancs
    if ( $this->assistanceTime_minutes > 0 ) {
      $reservationDuration = $tripData->duration->value + ($this->assistanceTime_minutes * 60);
    }else {
      $reservationDuration = $tripData->duration->value;
    }

    // Get a list of all the available cars
    foreach (Vehicles::where('active',true)->where('vehicle_type',$vehicleType->id)->get() as $key => $vehicle) {
      // Get car's current booking
      if ($vehicle->available($this->requestDate,$this->depatureTime,$reservationDuration)) {
        // Return car information
        $response->status = 'available'; // default
        $response->vehicleOne = 'available';
        $response->vehicleOneInfo = $vehicle;

        // Leave since availabl
        break;

      }// end if

    } // end foreach loop

    if (($vehicleType->id === 1) && ($response->status != 'available')) {
      // Get a list of all the available cars
      foreach (Vehicles::where('active',true)->where('vehicle_type',2)->get() as $key => $vehicle) {
        // Get car's current booking
        if ($vehicle->available($this->requestDate,$this->depatureTime,$reservationDuration)) {
          // Return car information
          $response->status = 'available'; // default
          $response->vehicleOne = 'available';
          $response->vehicleOneInfo = $vehicle;

          // Leave since availabl
          break;

        }// end if

      } // end foreach loop
    }

    // Return
    return $response;

  }
  public function googleAPI()
  {
    // Google maps variable
    $origin = urlencode($this->departureAddress);
    $destinations = urlencode($this->destinationAddress);
    $key = urlencode('AIzaSyBsPcPEknuG-DuUPtxv2lmrZO6jvt1FWzU');

    // Get the time between locations
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=$origin&destinations=$destinations&key=$key";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    $response_google = json_decode($response);

    // Get status
    $status = $response_google->rows[0]->elements[0]->status;

    // Get response
    $response = new \stdClass; // Instantiate stdClass object

    if ($status === 'ZERO_RESULTS') {
      $response->status = 'ZERO_RESULTS';
    }else {
      $response->status = 'OK';
      $response->distance = $response_google->rows[0]->elements[0]->distance;
      $response->duration = $response_google->rows[0]->elements[0]->duration;
    }

    return $response;
  }
  private function tripCost($duration, $distance, $assitance, $two_way)
  {
    // Determine vehicle costs
    $cost = ($duration / 60) * 1.19 + ($distance / 1000.0) * 1.25;

    // Get two way cost
    if ($two_way) {
      $cost += $cost*.8;
    }

    // Factor in assitance
    $cost += ($assitance / 60) * 28;

    // Set min
    if ($cost < 50) {
      $cost = 50;
    }

    return $cost * 100 * 1.13; // Cosdt in cents with taxes
  }

}
