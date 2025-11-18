# Abandoned Cart Reminders

Send automated reminder emails to customers who abandon their shopping carts. Simple Laravel app with configurable intervals.

## What It Does

- Add products to cart via API
- Send 3 reminder emails at custom intervals (default: 1h, 6h, 24h)
- Complete purchase from email link
- Auto-cancel reminders when order finalized
- All async with Redis queues

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Start services
make serve    # Laravel server
make queue    # Queue worker (needed for emails)

# Test it
make test     # 31 tests pass
```

## Config

Edit `.env`:

```env
CART_REMINDER_1_HOURS=1    # First reminder
CART_REMINDER_2_HOURS=6    # Second reminder
CART_REMINDER_3_HOURS=24   # Third reminder
```

## Email Setup

For dev, use Mailtrap. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
APP_URL=http://127.0.0.1:8000
```

## API Usage

**Add product:**
```bash
curl -X POST http://127.0.0.1:8000/api/cart/add-product \
  -H "Content-Type: application/json" \
  -d '{"customer_email":"test@example.com","product_id":1,"quantity":2}'
```

**Get cart:**
```bash
curl "http://127.0.0.1:8000/api/cart/active?customer_email=test@example.com"
```

**Finalize:**
```bash
curl -X POST http://127.0.0.1:8000/api/cart/1/finalize
```

## How It Works

1. Customer adds product → creates cart
2. System schedules 3 reminder emails
3. Each email has secure link to complete order
4. Customer clicks link → cart finalized, remaining emails cancelled

**Security:** HMAC-signed tokens, validated before cart completion.

## Testing

31 tests, 102 assertions. Run with:

```bash
make test
```

Tests cover: API endpoints, reminder scheduling, cart finalization, token validation, edge cases.

## Tech Stack

- Laravel 12
- Redis (queues)
- Nginx config included
- SQLite dev
## Production Notes

- Use Supervisor for queue workers
- Set `APP_ENV=production`
- Nginx config in `nginx.conf`
- Check logs in `storage/logs/`

---

Built with clean code, service layer architecture, and comprehensive tests.
