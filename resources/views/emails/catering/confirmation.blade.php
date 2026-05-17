<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Inquiry Received</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #7c3aed, #6d28d9); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Catering Inquiry Received</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">{{ $inquiry->restaurantSite->business_name }}</p>
    </div>

    <div style="padding: 20px;">
        <p>Hi {{ $inquiry->customer_name }},</p>

        <p>Thank you for your catering inquiry with <strong>{{ $inquiry->restaurantSite->business_name }}</strong>. We've received your request and will be in touch shortly.</p>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2 style="margin-top: 0; color: #333; font-size: 18px;">Your Inquiry Details</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Reference:</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $inquiry->inquiry_number }}</td>
                </tr>
                @if($inquiry->event_date)
                <tr>
                    <td style="padding: 8px 0; color: #666;">Event Date:</td>
                    <td style="padding: 8px 0;">{{ $inquiry->formatted_date }}</td>
                </tr>
                @endif
                @if($inquiry->guest_count)
                <tr>
                    <td style="padding: 8px 0; color: #666;">Guests:</td>
                    <td style="padding: 8px 0;">{{ $inquiry->guest_count }}</td>
                </tr>
                @endif
                @if($inquiry->cateringPackage)
                <tr>
                    <td style="padding: 8px 0; color: #666;">Package:</td>
                    <td style="padding: 8px 0;">{{ $inquiry->cateringPackage->name }}</td>
                </tr>
                @endif
            </table>
        </div>

        <p>If you have any questions in the meantime, you can reach us at:</p>
        <ul style="padding-left: 20px;">
            @if($inquiry->restaurantSite->phone)
            <li>Phone: <a href="tel:{{ $inquiry->restaurantSite->phone }}" style="color: #2563eb;">{{ $inquiry->restaurantSite->phone }}</a></li>
            @endif
            @if($inquiry->restaurantSite->email)
            <li>Email: <a href="mailto:{{ $inquiry->restaurantSite->email }}" style="color: #2563eb;">{{ $inquiry->restaurantSite->email }}</a></li>
            @endif
        </ul>

        <p>Thank you,<br>{{ $inquiry->restaurantSite->business_name }}</p>
    </div>

    <div style="text-align: center; color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <p>Powered by MenuDirect</p>
    </div>
</body>
</html>
