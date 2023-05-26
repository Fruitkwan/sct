@extends('layouts.app')

@section('content')
<!-- Top Bar Start -->
  <div class="top-bar">
    <a href="">
      <img src="{{asset('img/fake-logo.png')}}" class="top-logo">
    </a>
    <div class="menu-holder">
      <div class="menu-left">
        @if(Auth::guard('web')->user()->authorizeRoles(['clerk']))
          <ul class="main-menu">
            <li><a href="{{route('clerk.dashboard')}}">Clerk Dashboard</a></li>
          </ul>
        @endif
      </div>
      <div class="menu-right">
        <ul class="main-menu">
          <!-- This will show a return button to the admin account. They are coming from their  -->
          @if(Auth::guard('admin')->check())
            <li><a href="{{route('admin.dashboard')}}">Return</a></li>
          @else
          <li><a href="{{route('user.logout')}}">Log Out</a></li>
          @endif
        </ul>
      </div>
    </div>
    <a class="mobile-menu-toggle" href="#"><i class="fas fa-bars">///</i><i class="fas fa-times"></i></a>
  </div>
  <!-- Top Bar End -->

  <!-- Dashboard Start -->
  <div class="dashboard">
    <div class="col-50">
      <div class="col-100 vert-100 padded">
        <div class="block">
            <h1>My Rides
              @if($user->bookingValidation)
                <a class="in-title-button lightbox-toggle" href="#lightbox-book-ride" onclick="getBlankBookRideLightbox();"><span>Book a Ride </span><i class="fas fa-plus"></i></a>
              @endif
            </h1>
            <div class="block-content " style="max-height:950px; overflow-y: scroll;">
              @if(count($userBookings) > 0)
                @foreach($userBookings as $key=>$userBooking)
                  @if($userBooking->status == 'archived')
                    <!-- Ride Block Start -->
                    <div class="ride-block ride-archived">
                      <div class="ride-info" style="height: 300px;">
                        <div class="date-info" style="padding-top: 60px;">
                          <p class="month">$userBooking->month</p>
                          <p class="day">$userBooking->day</p>
                          <p class="year">$userBooking->year</p>
                          <p class="time">@ $userBooking->depatureTime</p>
                        </div>

                        <div class="destination-info">
                          <h2>Departure </h2>
                          <p>{{$userBooking->departureAddress}}</p>
                          <h2>Destination</h2>
                          <p>{{$userBooking->destinationAddress}}</p>
                        </div>

                        <div class="vehical-info">
                          @if($userBooking->vehicle->type->seats === 3)
                            <img class="feedback-image" src="{{asset('img/CarIcons-Sedan.png')}}">
                          @elseif($userBooking->vehicle->type->seats === 6)
                            <img class="feedback-image" src="{{asset('img/CarIcons-MiniVan.png')}}">
                          @elseif($userBooking->vehicle->type->seats === 2)
                            <img class="feedback-image" src="{{asset('img/CarIcons-Luxury.png')}}">
                          @elseif($userBooking->vehicle->type->seats === 12)
                            <img class="feedback-image" src="{{asset('img/CarIcons-MiniVan.png')}}">
                          @endif
                          <p class="car-description"><span>{{$userBooking->vehicle->type->type}}</span><br>{{$userBooking->vehicle->type->seats}} Passenger Limit</p>
                        </div>

                      </div>
                      <div class="action-icons">
                        <p><span>Status:</span> {{ucfirst($userBooking->status)}}</p>
                        <form id="delete_{{$userBooking->id}}" action="{{ route('booking.delete.submit',[$user->uuid,$userBooking->id]) }}" method="post">
                           <input type="hidden" name="_method" value="delete" />
                           <input type="hidden" name="_token" value="{{ csrf_token() }}">
                       </form>
                      </div>
                    </div>
                    <!-- Ride Block End -->
                  @else
                    <!-- Ride Block Start -->
                    <div class="ride-block ride-scheduled">
                      <div class="ride-info">
                        <div class="date-info">
                          <p class="month">{{$userBooking->month}}</p>
                          <p class="day">{{$userBooking->day}}</p>
                          <p class="year">{{$userBooking->year}}</p>
                          <p class="time">@ {{$userBooking->depatureTime}}</p>
                        </div>

                        <div class="destination-info">
                          <h2>Departure</h2>
                          <p>{{$userBooking->departureAddress}} </p>
                          <h2>Destination</h2>
                          <p>{{$userBooking->destinationAddress}}</p>
                          @if($userBooking->tripType === 'two-way')
                            <p>Pick-up at {{$userBooking->returnTime}}</p>
                          @endif
                        </div>

                        <div class="vehical-info">
                          @if($userBooking->vehicle->type->seats === 3)
                            <img class="feedback-image" src="{{asset('img/CarIcons-Sedan.png')}}">
                          @elseif($userBooking->vehicle->type->seats === 6)
                            <img class="feedback-image" src="{{asset('img/CarIcons-MiniVan.png')}}">
                          @elseif($userBooking->vehicle->type->seats === 2)
                            <img class="feedback-image" src="{{asset('img/CarIcons-Luxury.png')}}">
                          @elseif($userBooking->vehicle->type->seats === 10)
                            <img class="feedback-image" src="{{asset('img/CarIcons-MiniVan.png')}}">
                          @endif
                          <p class="car-description"><span>{{$userBooking->vehicle->type->type}}</span><br>{{$userBooking->vehicle->type->seats}} Passenger Limit</p>
                        </div>
                      </div>
                      <div class="action-icons">
                        <p><span>Cost (taxes included):</span> ${{number_format($userBooking->cost_cents/100, 2, '.', '')}} | <span>Type:</span> {{ucfirst($userBooking->tripType)}} | <span>Status:</span> {{ucfirst($userBooking->status)}}</p>
                        <!-- <a class="action-link icon-edit lightbox-toggle" href="#lightbox-book-ride"><i class="fas fa-edit"></i></a> -->
                        @if($userBooking->delete_falsy)
                          @if(!($userBooking->status === 'cancelled' || $userBooking->status === 'completed'))
                            <a class="action-link icon-trash" href="javascript:{}" onclick="document.getElementById('delete_{{$userBooking->id}}').submit();"><i class="fas fa-trash-alt"></i></a>
                          @endif
                        @endif                        <!-- <a class="action-link icon-money"><i class="fas fa-money-check"></i></a> -->

                        <form id="delete_{{$userBooking->id}}" action="{{ route('booking.delete.submit',[$user->uuid,$userBooking->id]) }}" method="post">
                           <input type="hidden" name="_method" value="delete" />
                           <input type="hidden" name="_token" value="{{ csrf_token() }}">
                       </form>
                      </div>
                    </div>
                    <!-- Ride Block End -->
                  @endif
                @endforeach
                <!-- Load more -->
                <!-- <p class="load-more"><a>Load More <i class="fas fa-plus"></i></a></p> -->

              @else

                <!-- No rides booked -->
                <p class="only-empty">You have no rides yet. Press book ride to schedule your first.</p>
              @endif

            </div>
          </div>
      </div>
    </div>

    <div class="col-50">
      <!-- Billing Section -->
      <div class="col-100 vert-50 padded">
        <div class="block">
            <h1>Billing
              <a class="in-title-button lightbox-toggle" href="#lightbox-receipts"><span>Receipts </span><i class="fas fa-receipt"></i></a>
            </h1>
            <div class="block-content">
              <div class="billing-block">
                <div class="billing-status">
                  <p class="status-icon">
                    @if($user->bookingValidation)
                      <i class="fas fa-check-circle cc-status-valid"></i>
                      <p class="status-name">Valid</p>
                    @else
                      <i class="fas fa-exclamation-triangle cc-status-warning" style="color:#ff6666;"></i>
                      <p class="status-name">Action Needed</p>
                    @endif
                  </p>
                </div>
                <div class="billing-info">
                  @if($user->bookingValidation)
                    <h2>Credit Card Number</h2>
                    <p>xxxx-xxxx-xxxx-<span>{{$user->card_last_four}}</span></p>
                    <h2>Card Type</h2>
                    <p>{{$user->card_brand}}</p>
                    <p style="margin-top:5px; margin-bottom: 0px;">
                      <form action="{{route('user.update.credit.card.submit',[$user->uuid])}}" method="POST" id="stripe-form">
                        {{ csrf_field() }}

                           <div class="stripe-form">
                            <script
                              src="https://checkout.stripe.com/checkout.js"
                              class="stripe-button"
                              data-key="{{env('STRIPE_KEY')}}"
                              data-name="Senior Care Transport"
                              data-description="Credit card for SCT trips."
                              data-image="{{ asset('img/logo_circle.png') }}"
                              data-panel-label="Update Card Details"
                              data-label="Change Credit Card Information"
                              data-allow-remember-me=true
                              data-locale="auto">
                            </script>
                        </div>

                      </form>
                    </p>
                  @else
                    <h2>Missing Credit Card</h2>
                    <p>We have no credit card on file, please prodive a valide credit card.</p>
                    <p>
                      <form action="{{route('user.update.credit.card.submit',[$user->uuid])}}" method="POST" id="stripe-form">
                        {{ csrf_field() }}
                           <div class="stripe-form">
                            <script
                              src="https://checkout.stripe.com/checkout.js"
                              class="stripe-button"
                              data-key="{{env('STRIPE_KEY')}}"
                              data-name="Senior Care Transport"
                              data-description="Credit card for SCT trips."
                              data-image="{{ asset('img/logo_circle.png') }}"
                              data-panel-label="Update Card Details"
                              data-label="Add Credit Card"
                              data-allow-remember-me=true
                              data-locale="auto">
                            </script>
                        </div>

                      </form>
                    </p>
                  @endif

                </div>
              </div>
            </div>
          </div>
      </div>
      <!-- Billing Section Ends -->

      <!-- Locations Section -->
      <div class="col-100 vert-50 padded">
        <div class="block">
            <h1>My Locations <a class="in-title-button lightbox-toggle inactive" href="#lightbox-locations" onclick="newLocation()"><span>New </span><i class="fas fa-plus"></i></a></h1>
            <div class="block-content ">

              @if($userLocations->count() > 0)

                @foreach($userLocations as $key=>$userLocation)
                  <div class="location-block">
                    <div class="location-info">
                      <p><b>{{$userLocation->nickname}}</b></p>
                      <p>{{$userLocation->google_address}}</p>
                    </div>
                    <div class="action-items">
                      <a class="icon-book lightbox-toggle" href="#lightbox-book-ride" onclick="getBookRideLightbox({{$userLocation}});" ><span>Book Ride </span><i class="fas fa-plus"></i></a>
                      <a class="action-link icon-edit lightbox-toggle" href="#lightbox-locations" onclick="getLocationLightbox({{$userLocation}})"><i class="fas fa-edit"></i></a>
                      <a class="action-link icon-trash" href="javascript:{}" onclick="document.getElementById('delete_location_{{$userLocation->id}}').submit();"><i class="fas fa-trash-alt"></i></a>

                      <form id="delete_location_{{$userLocation->id}}" action="{{route('user.location.delete',[$user->uuid, $userLocation->id])}}" method="post">
                           <input type="hidden" name="_method" value="delete" />
                           <input type="hidden" name="_token" value="{{ csrf_token() }}">
                      </form>

                    </div>
                  </div>
                @endforeach

              @else
                <!-- Favourite Location Block -->
                <p class="if-no-entry">Add a new location with the "New +" button above to quickly book common destinations.</p>

                <div class="location-block example-block">
                  <div class="location-info">
                    <p><b>Example</b></p>
                    <p>123 Example, Ottawa, ON</p>
                  </div>
                  <div class="action-items">
                    <a class="icon-book lightbox-toggle"disabled><span>Book Ride </span><i class="fas fa-plus"></i></a>
                    <a class="action-link icon-edit lightbox-toggle"disabled><i class="fas fa-edit"></i></a>
                    <a class="action-link icon-trash"disabled><i class="fas fa-trash-alt"></i></a>
                  </div>
                </div>
                <!-- End Favourite Location Block -->
              @endif

            </div>
          </div>
      </div>
      <!-- Locations Section -->

      <!-- Residence Section -->
      <div class="col-100 vert-50 padded">
        <div class="block">
            <h1>Residence</h1>
            <div class="block-content ">
              <h2 class="sub-title">Allow Access via Token</h2>
              <form action="{{route('user.add.clerk.token.submit',[$user->uuid])}}" method="POST" >
                @csrf

                <div class="input-holder">
                  <label for="token">Clerk Token</label>
                  <input type="text" class="form-control" name="token" required >
                </div>
                <div class="input-holder">
                  <input class="button-link reverse small left-pull" type="submit" value="Add">
                  <p class="error-message" style="color:white;">{{ $errors->first('token') }}</p>
                </div>
              </form>

                @foreach($clerks as $key=>$clerk)
                  <div class="location-block">
                    <div class="location-info">
                      <p><b>Clerk with Access:</b></p>
                      <p>{{$clerk->name}}</p>
                    </div>
                    <div class="action-items">
                      <a class="action-link icon-trash" href="javascript:{}" onclick="document.getElementById('delete_clerk_{{$key}}').submit();"><i class="fas fa-trash-alt"></i></a>

                      <form id="delete_clerk_{{$key}}" action="{{route('user.clerk.access.delete',[$user->uuid])}}" method="post">
                         <input type="hidden" name="_method" value="delete" />
                         <input type="hidden" name="_token" value="{{ csrf_token() }}">
                         <input type="hidden" name="clerk_uuid" value="{{$clerk->uuid}}">
                      </form>

                    </div>
                  </div>
                @endforeach

            </div>
          </div>
      </div>
      <!-- Residence Section End-->

    </div>



  </div>
  <!-- Dashboard End -->

  <!-- LightBox My Location -->
  <div class="lightbox" id="lightbox-receipts">
    <div class="lightbox-holder" style="overflow: visible;">
      <!-- Lightbox Header -->
      <div class="lightbox-header">
        <h1>My Receipts</h1>
        <a class="close-lightbox"><i class="fas fa-times"></i></a>
      </div>
      <!-- Lightbox Body -->
      <div class="lightbox-body">
        <!-- Add / remove class "active" to "section-display" to toggle views -->
        <!-- Display 1 -->
        <div class="section-display active" id="location-form">
            @foreach ($user->invoices() as $invoice)
              <h3>
                {{ $invoice->date()->toFormattedDateString() }} {{ $invoice->total() }}
                <a href="{{route('user.invoice',[$user->uuid, $invoice->id ])}}">download</a>

              </h3>



            @endforeach
          </table>
        </div>
      <!-- Lightbox Body End -->
    </div>
  </div>
</div>

  <!-- Light Box End -->

  <!-- LightBox My Location -->
  <div class="lightbox" id="lightbox-locations">
    <div class="lightbox-holder" style="overflow: visible;">
      <!-- Lightbox Header -->
      <div class="lightbox-header">
        <h1>My Location</h1>
        <a class="close-lightbox"><i class="fas fa-times"></i></a>
      </div>
      <!-- Lightbox Body -->
      <div class="lightbox-body">
        <!-- Add / remove class "active" to "section-display" to toggle views -->
        <!-- Display 1 -->
        <div class="section-display active" id="location-form">
          <!-- <p style="text-align: center;"></p> -->
          <form method="POST" >
            <!-- Sync with google maps api -->
            <div class="input-holder">
              <label>Nickname <i class="fas fa-pencil-alt"></i></label>
              <input id="nickname" type="text" placeholder="Nickname for location">
              <i id="nickname-checkmark" class="fa fa-check popUpCheckmark"></i>
              <span id="nickname-error" class="popUpError"></span>

            </div>
            <!-- Sync with google maps api -->
            <div class="input-holder">
              <label>Location <i class="fas fa-map-marker-alt"></i></label>
              <input id="autocomplete" placeholder="Enter your address..." type="text"></input>
              <i id="autocomplete-checkmark" class="fa fa-check popUpCheckmark"></i>
              <span id="autocomplete-error" class="popUpError"></span>
            </div>

            <!-- Adress info -->
            <input type="hidden" id="street_number" disabled="true">
            <input type="hidden" id="route" disabled="true">
            <input type="hidden" id="locality" disabled="true">
            <input type="hidden" id="administrative_area_level_1" disabled="true">
            <input type="hidden" id="country" disabled="true">
            <input type="hidden" id="postal_code" disabled="true">

            <!-- Submit -->
            <div class="input-holder full-width" style="margin-top: 10px; margin-bottom: 20px;">
              <input id="addFavoriteLocation" class="button-link reverse" type="submit" value="Save">
            </div>
          </form>
        </div>
        <!-- Display End -->
        <!-- Display 2 -->
        <div class="section-display" id="location-success">
          <h2 style="text-align: center;">Success!</h2>
          <p class="feedback-info">"513 Karma Lane" has been added to you locations as "Home".</p>
          <img class="feedback-image" src="assets/img/circle-check.png">
          <form action="/bookRide" method="POST" style="margin-top: 20px;">
            <div class="input-holder full-width">
              <input id="submit" class="button-link reverse" type="submit" value="Okay">
            </div>
          </form>
        </div>
        <!-- Display End -->
      </div>
      <!-- Lightbox Body End -->
    </div>
  </div>
  <!-- Light Box End -->

  <!-- LightBox Book A Ride -->
  <div class="lightbox" id="lightbox-book-ride" style="overflow: visible;">
    <div class="lightbox-holder" style="overflow: hidden;">
      <!-- Lightbox Header -->
      <div class="lightbox-header">
        <h1>Book a Ride</h1>
        <a class="close-lightbox"><i class="fas fa-times"></i></a>
      </div>
      <!-- Lightbox Body -->
      <div class="lightbox-body">
        <!-- Add / remove class "active" to "section-display" to toggle views -->
        <!-- Display 1 -->
        <div class="section-display active" id="booking-form">
          <!-- <p style="text-align: center;"></p> -->
          <form >
            <!-- Pick Date -->
            <div class="input-holder half-width">
              <label>Pick Up Date <i class="far fa-calendar"></i></label>
              <input id="date" type="date">
              <i id="date-checkmark" class="fa fa-check popUpCheckmark"></i>
              <span id="date-error" class="popUpError"></span>
            </div>
            <!-- Pick Time -->
            <div class="input-holder half-width">
              <label>Pick Up Time <i class="far fa-clock"></i></label>
              <input id="depatureTime" type="time">
              <i id="depatureTime-checkmark" class="fa fa-check popUpCheckmark"></i>
              <span id="depatureTime-error" class="popUpError"></span>
            </div>
            <hr/>

            <div class="input-holder">
             <label> Return Pick Up Time (Leave blank for one-way) <i class="far fa-clock"></i></label>
             <input id="returnTime" type="time" class="notVisible">
           </div>

           <hr/>
            <!-- Sync with google maps api -->
            <div class="input-holder">
              <label>From <i class="fas fa-map-marker-alt"></i></label>
              <input id="autocompleteDepatureAddress" placeholder="Enter your address..." type="text"></input>
              <i id="autocompleteDepatureAddress-checkmark" class="fa fa-check popUpCheckmark"></i>
              <span id="autocompleteDepatureAddress-error" class="popUpError"></span>
            </div>

            <div class="input-holder">
              <label>To <i class="fas fa-map-marker-alt"></i></label>
              <input id="autocompleteDestinationAddress" placeholder="Enter your address..." type="text"></input>
              <i id="autocompleteDestinationAddress-checkmark" class="fa fa-check popUpCheckmark"></i>
              <span id="autocompleteDestinationAddress-error" class="popUpError"></span>
            </div>
            <hr/>

            <div class="input-holder">
              <label>Assistance (Leave blank if no)<i class="fas fa-question"></i></label>
              <textarea id="assistanceInstructions" name="assistanceInstructions" placeholder="I need help with..."></textarea>
            </div>

            <hr/>
            <!-- Car Type -->
            <div class="input-holder ">
              <label>Vehical Size <i class="fas fa-car"></i></label>
              <div class="radio-holder" style="margin-left:5%; margin-right:5%;">
                <input type="radio" name="cartype" value="small">
                <img class="feedback-image" src="{{asset('img/CarIcons-Sedan.png')}}">
                <p class="car-description"><span>Sedan</span><br>3 Passenger Limit</p>
              </div>
              <div class="radio-holder" style="margin-left:5%; margin-right:5%;">
                <input type="radio" name="cartype" value="medium">
                <img class="feedback-image" src="{{asset('img/CarIcons-MiniVan.png')}}">
                <p class="car-description"><span>Van</span><br>6 Passenger Limit</p>
              </div>
              <div class="radio-holder" style="margin-left:5%; margin-right:5%;">
                <input type="radio" name="cartype" value="large">
                <img class="feedback-image" src="{{asset('img/CarIcons-MiniVan.png')}}">
                <p class="car-description"><span>Mini Bus</span><br>+12 Passenger Limit</p>
              </div>
            </div>

            <hr/>

            <!-- Notes -->
             <div class="input-holder">
              <label>Notes For Drive <i class="far fa-comment"></i></label>
              <textarea id="notes" placeholder="Please pick me up by..."></textarea>
            </div>


            <!-- Submit -->
            <div class="input-holder full-width" style="margin-top: 10px; margin-bottom: 20px;">
              <input id="reservationCheck" class="button-link reverse" type="submit" value="Request">
            </div>
          </form>
        </div>
        <!-- Display End -->
        <!-- Display 2 -->
        <div class="section-display " id="booking-confirm">
          <h2 style="text-align: center;">Booking Confirmation</h2>
            <div class="ride-block ride-scheduled">
              <div class="ride-info">
                <div class="date-info">
                  <p id="confirm-month">(taxes included) $</p>
                  <p id="confirm-day" class="day" style="font-size: 40pt;"></p>
                  <p id="confirm-year" class="year"></p>
                  <p id="confirm-time" class="time"></p>
                </div>

                <div class="destination-info">
                  <p id="confirm-type"></p>
                  <p id="confirm-pickup"></p>
                  <p id="confirm-dropoff"></p>
                  <p id="confirm-return"></p>

                </div>
              </div>
            </div>


            <form style="margin-top: 20px;">
              <div class="input-holder full-width">
                <button class="button-link reverse" onclick="event.preventDefault(); acceptBooking();">Book</button>
                <button class="button-link reverse" onclick="event.preventDefault(); editBooking();" style="float: left;">Edit</button>
              </div>
            </form>

        </div>

        <div class="section-display" id="booking-success">
          <h2 style="text-align: center;">Success</h2>
          <p class="feedback-info"></p>
          <img class="feedback-image" src="{{asset('img/circle-check.png')}}">
          <form style="margin-top: 20px;">
            <div class="input-holder full-width">
              <input onClick="window.location.reload()" id="submit" class="button-link reverse" type="submit" value="Okay">
            </div>
          </form>
        </div>
        <!-- Display End -->
        <!-- Display 4 -->
        <div class="section-display " id="booking-error">
          <h2 style="text-align: center;">Booking Request</h2>
          <p class="feedback-info">There was a conflict will autocomplete your booking. <br>Please click below to send in request and we will get back to you.<br>Thank you </p>
          <form action="/bookRide" method="POST" style="margin-top: 20px;">
            <div class="input-holder full-width">
              <button class="button-link reverse" onclick="event.preventDefault(); sendSpecialBooking();">Send</button>
              <button class="button-link reverse" style="float: left;" onclick="event.preventDefault(); showEditBooking();">Edit</button>
            </div>
          </form>
        </div>
        <!-- Display End -->
      </div>
      <!-- Lightbox Body End -->
    </div>
  </div>
  <!-- Light Box End -->
@endsection
