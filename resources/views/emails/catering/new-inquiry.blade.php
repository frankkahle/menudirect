<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Catering Inquiry #{{ $inquiry->inquiry_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #7c3aed, #6d28d9); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">New Catering Inquiry!</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">{{ $inquiry->inquiry_number }}</p>
    </div>

    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
        <strong style="color: #856404;">Action Required:</strong>
        <span style="color: #856404;">Please respond to this catering inquiry as soon as possible.</span>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h2 style="margin-top: 0; color: #333; font-size: 18px;">Customer Information</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Name:</td>
                <td style="padding: 8px 0; font-weight: bold;">{{ $inquiry->customer_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Phone:</td>
                <td style="padding: 8px 0;"><a href="tel:{{ $inquiry->customer_phone }}" style="color: #2563eb;">{{ $inquiry->customer_phone }}</a></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Email:</td>
                <td style="padding: 8px 0;"><a href="mailto:{{ $inquiry->customer_email }}" style="color: #2563eb;">{{ $inquiry->customer_email }}</a></td>
            </tr>
        </table>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h2 style="margin-top: 0; color: #333; font-size: 18px;">Event Details</h2>
        <table style="width: 100%; border-collapse: collapse;">
            @if($inquiry->event_date)
            <tr>
                <td style="padding: 8px 0; color: #666;">Date:</td>
                <td style="padding: 8px 0; font-weight: bold;">{{ $inquiry->formatted_date }}</td>
            </tr>
            @endif
            @if($inquiry->event_time)
            <tr>
                <td style="padding: 8px 0; color: #666;">Time:</td>
                <td style="padding: 8px 0;">{{ $inquiry->event_time }}</td>
            </tr>
            @endif
            @if($inquiry->guest_count)
            <tr>
                <td style="padding: 8px 0; color: #666;">Guests:</td>
                <td style="padding: 8px 0; font-weight: bold;">{{ $inquiry->guest_count }}</td>
            </tr>
            @endif
            @if($inquiry->event_type)
            <tr>
                <td style="padding: 8px 0; color: #666;">Event Type:</td>
                <td style="padding: 8px 0;">{{ $inquiry->event_type_label }}</td>
            </tr>
            @endif
            @if($inquiry->cateringPackage)
            <tr>
                <td style="padding: 8px 0; color: #666;">Package:</td>
                <td style="padding: 8px 0;">
                    <span style="display: inline-block; padding: 4px 12px; background: #ede9fe; color: #6d28d9; border-radius: 20px; font-size: 14px;">
                        {{ $inquiry->cateringPackage->name }} ({{ $inquiry->cateringPackage->formatted_price }})
                    </span>
                </td>
            </tr>
            @endif
        </table>
    </div>

    @if($inquiry->message)
    <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <strong style="color: #92400e;">Customer Message:</strong>
        <p style="margin: 10px 0 0; color: #92400e;">{{ $inquiry->message }}</p>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/client/restaurant/{{ $inquiry->restaurant_site_id }}/catering/{{ $inquiry->id }}"
           style="display: inline-block; background: #7c3aed; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
            View Inquiry in Dashboard
        </a>
    </div>

    <div style="text-align: center; color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <p>Inquiry received at {{ $inquiry->created_at->format('F j, Y g:i A') }}</p>
    </div>
</body>
</html>
