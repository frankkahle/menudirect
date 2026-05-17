<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Reservation {{ $reservation->confirmation_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #7c3aed, #6d28d9); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">New Reservation!</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">{{ $reservation->confirmation_number }}</p>
    </div>

    @if($reservation->status === 'pending')
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
        <strong style="color: #856404;">Action Required:</strong>
        <span style="color: #856404;">Please confirm this reservation.</span>
    </div>
    @else
    <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0;">
        <strong style="color: #065f46;">Auto-Confirmed</strong>
        <span style="color: #065f46;">This reservation has been automatically confirmed.</span>
    </div>
    @endif

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h2 style="margin-top: 0; color: #333; font-size: 18px;">Reservation Details</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Guest:</td>
                <td style="padding: 8px 0; font-weight: bold;">{{ $reservation->customer_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Phone:</td>
                <td style="padding: 8px 0;"><a href="tel:{{ $reservation->customer_phone }}" style="color: #2563eb;">{{ $reservation->customer_phone }}</a></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Email:</td>
                <td style="padding: 8px 0;"><a href="mailto:{{ $reservation->customer_email }}" style="color: #2563eb;">{{ $reservation->customer_email }}</a></td>
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
                <td style="padding: 8px 0;">
                    <span style="display: inline-block; padding: 4px 12px; background: #dbeafe; color: #1e40af; border-radius: 20px; font-size: 14px;">
                        {{ $reservation->party_size }} {{ Str::plural('guest', $reservation->party_size) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    @if($reservation->special_requests)
    <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <strong style="color: #92400e;">Special Requests:</strong>
        <p style="margin: 10px 0 0; color: #92400e;">{{ $reservation->special_requests }}</p>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/client/restaurant/{{ $reservation->restaurant_site_id }}/reservations/{{ $reservation->id }}"
           style="display: inline-block; background: #7c3aed; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
            View in Dashboard
        </a>
    </div>

    <div style="text-align: center; color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <p>Reservation received at {{ $reservation->created_at->format('F j, Y g:i A') }}</p>
    </div>
</body>
</html>
