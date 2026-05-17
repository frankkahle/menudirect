<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Update {{ $reservation->confirmation_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    @php
        $headerColor = match($reservation->status) {
            'confirmed' => 'linear-gradient(135deg, #10b981, #059669)',
            'cancelled' => 'linear-gradient(135deg, #ef4444, #dc2626)',
            default => 'linear-gradient(135deg, #6366f1, #4f46e5)',
        };
        $statusMessage = match($reservation->status) {
            'confirmed' => 'Your reservation has been confirmed!',
            'cancelled' => 'Your reservation has been cancelled.',
            'seated' => 'You have been seated. Enjoy your meal!',
            'completed' => 'Thank you for dining with us!',
            default => 'Your reservation has been updated.',
        };
    @endphp

    <div style="background: {{ $headerColor }}; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Reservation {{ $reservation->status_label }}</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">{{ $reservation->restaurantSite->business_name }}</p>
    </div>

    <div style="padding: 20px;">
        <p style="font-size: 16px;">{{ $statusMessage }}</p>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Confirmation #:</td>
                    <td style="padding: 8px 0; font-weight: bold; font-family: monospace;">{{ $reservation->confirmation_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Date:</td>
                    <td style="padding: 8px 0;">{{ $reservation->formatted_date }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Time:</td>
                    <td style="padding: 8px 0;">{{ $reservation->formatted_time }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Party Size:</td>
                    <td style="padding: 8px 0;">{{ $reservation->party_size }} {{ Str::plural('guest', $reservation->party_size) }}</td>
                </tr>
            </table>
        </div>

        @if($reservation->status === 'cancelled' && $reservation->cancellation_reason)
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;">
            <strong style="color: #991b1b;">Cancellation Reason:</strong>
            <p style="margin: 10px 0 0; color: #991b1b;">{{ $reservation->cancellation_reason }}</p>
        </div>
        @endif
    </div>

    <div style="text-align: center; margin: 20px 0;">
        <a href="{{ $reservation->getStatusUrl() }}"
           style="display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">
            View Reservation Details
        </a>
    </div>

    <div style="text-align: center; color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <p>Questions? Contact {{ $reservation->restaurantSite->business_name }}
        @if($reservation->restaurantSite->phone)
            at <a href="tel:{{ $reservation->restaurantSite->phone }}" style="color: #2563eb;">{{ $reservation->restaurantSite->phone }}</a>
        @endif
        </p>
    </div>
</body>
</html>
