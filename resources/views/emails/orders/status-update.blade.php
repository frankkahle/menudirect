<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Update #{{ $order->order_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    @php
        $headerColor = match($order->status) {
            'confirmed' => 'linear-gradient(135deg, #2563eb, #1d4ed8)',
            'preparing' => 'linear-gradient(135deg, #7c3aed, #6d28d9)',
            'ready' => 'linear-gradient(135deg, #16a34a, #15803d)',
            'completed' => 'linear-gradient(135deg, #6b7280, #4b5563)',
            'cancelled' => 'linear-gradient(135deg, #dc2626, #b91c1c)',
            default => 'linear-gradient(135deg, #2563eb, #1d4ed8)',
        };

        $statusIcon = match($order->status) {
            'confirmed' => '&#10003;',
            'preparing' => '&#128293;',
            'ready' => '&#127881;',
            'completed' => '&#11088;',
            'cancelled' => '&#10005;',
            default => '&#128276;',
        };

        $statusMessage = match($order->status) {
            'confirmed' => 'Your order has been confirmed and is in the queue!',
            'preparing' => 'The kitchen is preparing your food right now.',
            'ready' => $order->isPickup() ? 'Your order is ready for pickup!' : 'Your order is ready and heading your way!',
            'completed' => 'Your order has been completed. Thank you!',
            'cancelled' => 'Your order has been cancelled.',
            default => 'Your order status has been updated.',
        };
    @endphp

    <div style="background: {{ $headerColor }}; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <div style="font-size: 48px; margin-bottom: 10px;">{!! $statusIcon !!}</div>
        <h1 style="color: white; margin: 0; font-size: 24px;">{{ $order->status_label }}</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">Order #{{ $order->order_number }}</p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;">
        <p style="margin: 0; font-size: 16px; color: #333;">{{ $statusMessage }}</p>
    </div>

    @if($order->status === 'confirmed' && $order->estimated_ready_at)
    <div style="background: #dbeafe; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
        <p style="margin: 0; color: #1e40af; font-size: 14px;">Estimated Ready Time</p>
        <p style="margin: 5px 0 0; color: #1e40af; font-size: 24px; font-weight: bold;">{{ $order->estimated_ready_at->format('g:i A') }}</p>
    </div>
    @endif

    @if($order->status === 'ready')
    <div style="background: #dcfce7; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
        @if($order->isPickup())
        <p style="margin: 0; color: #166534; font-size: 16px;"><strong>Please pick up your order at:</strong></p>
        <p style="margin: 10px 0 0; color: #166534;">{{ $order->restaurantSite->address }}</p>
        @else
        <p style="margin: 0; color: #166534; font-size: 16px;"><strong>Your order is on its way to:</strong></p>
        <p style="margin: 10px 0 0; color: #166534;">{{ $order->delivery_address }}</p>
        @endif
    </div>
    @endif

    @if($order->status === 'cancelled')
    <div style="background: #fee2e2; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0; color: #991b1b;"><strong>Reason:</strong></p>
        <p style="margin: 10px 0 0; color: #991b1b;">{{ $order->cancellation_reason ?? 'No reason provided. Please contact the restaurant for more information.' }}</p>
    </div>
    @endif

    <div style="margin: 20px 0;">
        <h2 style="color: #333; font-size: 18px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Order Summary</h2>
        <table style="width: 100%; border-collapse: collapse;">
            @foreach($order->items as $item)
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 8px 0;">{{ $item->quantity }}x {{ $item->name }}</td>
                <td style="padding: 8px 0; text-align: right;">{{ $item->formatted_total }}</td>
            </tr>
            @endforeach
            <tr style="font-weight: bold; border-top: 2px solid #333;">
                <td style="padding: 12px 0;">Total:</td>
                <td style="padding: 12px 0; text-align: right;">{{ $order->formatted_total }}</td>
            </tr>
        </table>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $order->getTrackingUrl() }}"
           style="display: inline-block; background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
            Track Your Order
        </a>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; font-size: 16px;">{{ $order->restaurantSite->business_name }}</h3>
        <p style="margin: 5px 0;">
            <a href="tel:{{ $order->restaurantSite->phone }}" style="color: #2563eb;">{{ $order->restaurantSite->phone }}</a>
        </p>
        <p style="margin: 5px 0; color: #666; font-size: 14px;">{{ $order->restaurantSite->address }}</p>
    </div>

    <div style="text-align: center; color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <p>Questions about your order? Contact the restaurant directly.</p>
    </div>
</body>
</html>
