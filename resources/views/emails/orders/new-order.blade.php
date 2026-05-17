<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order #{{ $order->order_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #dc2626, #b91c1c); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">New Order Received!</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">Order #{{ $order->order_number }}</p>
    </div>

    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
        <strong style="color: #856404;">Action Required:</strong>
        <span style="color: #856404;">Please confirm this order as soon as possible.</span>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h2 style="margin-top: 0; color: #333; font-size: 18px;">Customer Information</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Name:</td>
                <td style="padding: 8px 0; font-weight: bold;">{{ $order->customer_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Phone:</td>
                <td style="padding: 8px 0;"><a href="tel:{{ $order->customer_phone }}" style="color: #2563eb;">{{ $order->customer_phone }}</a></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Email:</td>
                <td style="padding: 8px 0;"><a href="mailto:{{ $order->customer_email }}" style="color: #2563eb;">{{ $order->customer_email }}</a></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Order Type:</td>
                <td style="padding: 8px 0;">
                    <span style="display: inline-block; padding: 4px 12px; background: {{ $order->isDelivery() ? '#dbeafe' : '#dcfce7' }}; color: {{ $order->isDelivery() ? '#1e40af' : '#166534' }}; border-radius: 20px; font-size: 14px;">
                        {{ $order->order_type_label }}
                    </span>
                </td>
            </tr>
            @if($order->isDelivery() && $order->delivery_address)
            <tr>
                <td style="padding: 8px 0; color: #666; vertical-align: top;">Delivery Address:</td>
                <td style="padding: 8px 0;">{{ $order->delivery_address }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div style="margin: 20px 0;">
        <h2 style="color: #333; font-size: 18px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Order Items</h2>
        <table style="width: 100%; border-collapse: collapse;">
            @foreach($order->items as $item)
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px 0;">
                    <strong>{{ $item->quantity }}x {{ $item->name }}</strong>
                    @if($item->special_requests)
                    <br><span style="color: #666; font-size: 14px;">Note: {{ $item->special_requests }}</span>
                    @endif
                </td>
                <td style="padding: 12px 0; text-align: right; font-weight: bold;">{{ $item->formatted_total }}</td>
            </tr>
            @endforeach
        </table>

        <table style="width: 100%; margin-top: 15px; border-top: 2px solid #333;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Subtotal:</td>
                <td style="padding: 8px 0; text-align: right;">{{ $order->formatted_subtotal }}</td>
            </tr>
            @if($order->tax_amount > 0)
            <tr>
                <td style="padding: 8px 0; color: #666;">Tax ({{ number_format($order->tax_rate * 100, 1) }}%):</td>
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
                <td style="padding: 12px 0;">Total:</td>
                <td style="padding: 12px 0; text-align: right; color: #dc2626;">{{ $order->formatted_total }}</td>
            </tr>
        </table>
    </div>

    @if($order->special_instructions)
    <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <strong style="color: #92400e;">Special Instructions:</strong>
        <p style="margin: 10px 0 0; color: #92400e;">{{ $order->special_instructions }}</p>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/client/restaurant/{{ $order->restaurant_site_id }}/orders/{{ $order->id }}"
           style="display: inline-block; background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
            View Order in Dashboard
        </a>
    </div>

    <div style="text-align: center; color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <p>Order received at {{ $order->created_at->format('F j, Y g:i A') }}</p>
        <p style="margin-top: 10px;">
            <strong>Payment:</strong> Pay at {{ $order->isPickup() ? 'Pickup' : 'Delivery' }}
        </p>
    </div>
</body>
</html>
