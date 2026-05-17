<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation {{ $reservation->confirmation_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Reservation {{ $reservation->status === 'confirmed' ? 'Confirmed' : 'Received' }}!</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">{{ $reservation->restaurantSite->business_name }}</p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h2 style="margin-top: 0; color: #333; font-size: 18px;">Your Reservation</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Confirmation #:</td>
                <td style="padding: 8px 0; font-weight: bold; font-family: monospace; font-size: 16px;">{{ $reservation->confirmation_number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Date:</td>
                <td style="padding: 8px 0; font-weight: bold;">{{ $reservation->formatted_date }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Time:</td>
                <td style="padding: 8px 0; font-weight: bold;">{{ $reservation->formatted_time }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Party Size:</td>
                <td style="padding: 8px 0;">{{ $reservation->party_size }} {{ Str::plural('guest', $reservation->party_size) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Status:</td>
                <td style="padding: 8px 0;">
                    <span style="display: inline-block; padding: 4px 12px; background: {{ $reservation->status === 'confirmed' ? '#d1fae5' : '#fef3c7' }}; color: {{ $reservation->status === 'confirmed' ? '#065f46' : '#92400e' }}; border-radius: 20px; font-size: 14px;">
                        {{ $reservation->status_label }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h2 style="margin-top: 0; color: #333; font-size: 18px;">Restaurant Details</h2>
        <p style="margin: 5px 0;"><strong>{{ $reservation->restaurantSite->business_name }}</strong></p>
        @if($reservation->restaurantSite->address)
        <p style="margin: 5px 0;">{{ $reservation->restaurantSite->address }}</p>
        @endif
        @if($reservation->restaurantSite->phone)
        <p style="margin: 5px 0;"><a href="tel:{{ $reservation->restaurantSite->phone }}" style="color: #2563eb;">{{ $reservation->restaurantSite->phone }}</a></p>
        @endif
    </div>

    @if($reservation->special_requests)
    <div style="margin: 20px 0;">
        <strong>Your Special Requests:</strong>
        <p style="color: #666;">{{ $reservation->special_requests }}</p>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $reservation->getStatusUrl() }}"
           style="display: inline-block; background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
            View or Manage Reservation
        </a>
    </div>

    <div style="text-align: center; color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <p>Need to cancel? Visit the link above or contact the restaurant directly.</p>
    </div>
</body>
</html>
