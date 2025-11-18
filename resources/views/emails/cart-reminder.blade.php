<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .cart-item { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .total { font-size: 18px; font-weight: bold; margin-top: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reminder #{{ $reminderNumber }}</h1>
        </div>
        <div class="content">
            <p>Hi there!</p>
            <p>You have items waiting in your cart. Don't miss out on your order!</p>
            
            <h3>Your Cart Items:</h3>
            @foreach($cart->items as $item)
            <div class="cart-item">
                <strong>{{ $item->product->name }}</strong><br>
                Quantity: {{ $item->quantity }} × ${{ number_format($item->price, 2) }}
                = ${{ number_format($item->getTotal(), 2) }}
            </div>
            @endforeach
            
            <div class="total">
                Total: ${{ number_format($cartTotal, 2) }}
            </div>
            
            <a href="{{ $completionUrl }}" class="button">Complete Your Order</a>
            
            <p style="margin-top: 30px; font-size: 12px; color: #666;">
                If you didn't create this cart, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>
</html>