<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #{{ $order->order_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Order Received!</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">Thank you for your order</p>
    </div>

    <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; margin: 20px 0;">
        <strong style="color: #166534;">Order #{{ $order->order_number }}</strong>
        <p style="color: #166534; margin: 5px 0 0;">
            We've received your order and will notify you when it's confirmed.
        </p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h2 style="margin-top: 0; color: #333; font-size: 18px;">{{ $order->restaurantSite->business_name }}</h2>
        <p style="margin: 5px 0; color: #666;">
            <strong>{{ $order->order_type_label }}</strong>
            @if($order->isDelivery())
             to {{ $order->delivery_address }}
            @else
             at {{ $order->restaurantSite->address }}
            @endif
        </p>
        @if($order->estimated_ready_at)
        <p style="margin: 10px 0 0; color: #666;">
            <strong>Estimated Ready:</strong> {{ $order->estimated_ready_at->format('g:i A') }}
        </p>
        @endif
    </div>

    <div style="margin: 20px 0;">
        <h2 style="color: #333; font-size: 18px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Your Order</h2>
        <table style="width: 100%; border-collapse: collapse;">
            @foreach($order->items as $item)
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px 0;">
                    <strong>{{ $item->quantity }}x {{ $item->name }}</strong>
                    @if($item->special_requests)
                    <br><span style="color: #666; font-size: 14px;">{{ $item->special_requests }}</span>
                    @endif
                </td>
                <td style="padding: 12px 0; text-align: right;">{{ $item->formatted_total }}</td>
            </tr>
            @endforeach
        </table>

        <table style="width: 100%; margin-top: 15px; border-top: 2px solid #eee;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Subtotal:</td>
                <td style="padding: 8px 0; text-align: right;">{{ $order->formatted_subtotal }}</td>
            </tr>
            @if($order->tax_amount > 0)
            <tr>
                <td style="padding: 8px 0; color: #666;">Tax:</td>
                <td style="padding: 8px 0; text-align: right;">{{ $order->formatted_tax }}</td>
            </tr>
            @endif
            @if($order->delivery_fee > 0)
            <tr>
                <td style="padding: 8px 0; color: #666;">Delivery Fee:</td>
                <td style="padding: 8px 0; text-align: right;">{{ $order->formatted_delivery_fee }}</td>
            </tr>
            @endif
            <tr style="font-size: 18px; font-weight: bold;">
                <td style="padding: 12px 0;">Total (Pay at {{ $order->isPickup() ? 'Pickup' : 'Delivery' }}):</td>
                <td style="padding: 12px 0; text-align: right; color: #2563eb;">{{ $order->formatted_total }}</td>
            </tr>
        </table>
    </div>

    @if($order->special_instructions)
    <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <strong>Your Instructions:</strong>
        <p style="margin: 10px 0 0; color: #666;">{{ $order->special_instructions }}</p>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $order->getTrackingUrl() }}"
           style="display: inline-block; background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
            Track Your Order
        </a>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; font-size: 16px;">Contact the Restaurant</h3>
        <p style="margin: 5px 0;">
            <a href="tel:{{ $order->restaurantSite->phone }}" style="color: #2563eb;">{{ $order->restaurantSite->phone }}</a>
        </p>
        <p style="margin: 5px 0; color: #666; font-size: 14px;">{{ $order->restaurantSite->address }}</p>
    </div>

    <div style="text-align: center; color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <p>This email confirms we received your order. You'll get another email when it's confirmed by the restaurant.</p>
    </div>
</body>
</html>
