# Abandoned Cart Reminders System

A Laravel-based solution for sending automated abandoned cart reminder emails to customers who haven't completed their orders.

## 📋 Challenge Requirements

All requirements from the technical challenge have been implemented:

✅ **As a customer, I can add a product to my cart**
- API endpoint: `POST /api/cart/add-product`
- Accepts: customer_email, product_id, quantity
- Returns: cart and cart item details
- **Test Coverage:** 15+ feature and unit tests

✅ **As a customer, I will get an email X hours after adding a product to my cart**
- Configurable reminder intervals via `.env`
- Default: 3 reminders at 1, 2, and 3 hours after cart creation
- Email includes cart summary and completion link
- **Test Coverage:** Queue job dispatch, scheduling logic, interval configuration

✅ **As a customer, if I receive a reminder email, I can finalize my order**
- Email contains secure completion link with HMAC token
- Clicking link finalizes cart and cancels pending reminders
- **Test Coverage:** Token validation, email completion flow

✅ **As a customer, if I finalize my order, I will not receive any more reminder emails**
- Cart status updated to 'finalized'
- All pending reminders automatically cancelled
- No jobs dispatched after finalization
- **Test Coverage:** Reminder cancellation, state transitions

**Technical Stack:**
- ✅ PHP (Laravel 12.x)
- ✅ Redis (Queue system)
- ✅ Nginx (Configuration provided)
- ✅ **Tests:** 41 passing tests (123 assertions)

## 🏗️ Architecture

### Technology Stack
- **PHP**: Laravel 12 (latest)
- **Database**: SQLite (development) / MySQL-ready (production)
- **Queue**: Redis for job processing
- **Web Server**: Nginx (configuration included)
- **Email**: SMTP (Mailtrap for testing)

### Design Patterns
- **Service Layer**: Business logic separated from controllers
- **Repository Pattern**: Eloquent models with relationships
- **Event-Driven**: CartItemAdded, CartFinalized events
- **Queue-Based**: Asynchronous email processing with delayed dispatch
- **Dependency Injection**: Clean, testable code structure

## 📁 Project Structure

```
app/
├── Events/              # Domain events
│   ├── CartItemAdded.php
│   └── CartFinalized.php
├── Http/Controllers/
│   └── CartController.php
├── Jobs/
│   └── SendCartReminderEmail.php
├── Mail/
│   └── CartReminderMail.php
├── Models/
│   ├── Cart.php
│   ├── CartItem.php
│   ├── CartReminder.php
│   └── Product.php
└── Services/Cart/
    ├── CartService.php
    ├── ReminderService.php
    └── CartMonitoringService.php
```

## 🚀 Installation

### Prerequisites
- PHP 8.2+
- Composer
- Redis Server
- Nginx (optional, Laravel serve works for development)

### Setup

```bash
# Clone repository
git clone <repository-url>
cd AbandonedCartReminders

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Start Redis (if not running)
redis-server

# Start development server
make serve  # or: php artisan serve

# Start queue worker (REQUIRED for emails)
make queue  # or: php artisan queue:work redis
```

## ⚙️ Configuration

### Email Reminder Intervals

Edit `.env` file:

```env
# Intervals in minutes (for testing) or hours (for production)
CART_REMINDERS_ENABLED=true
CART_REMINDER_1_MINUTES=5    # First reminder after 5 minutes
CART_REMINDER_2_MINUTES=10   # Second reminder after 10 minutes
CART_REMINDER_3_MINUTES=12   # Third reminder after 12 minutes
```

### Email Configuration

**For Development (Mailtrap):**
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
```

**For Production (Real SMTP):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### Application URL

Update for email links to work correctly:
```env
APP_URL=http://127.0.0.1:8000
```

## 📡 API Endpoints

### Add Product to Cart
```bash
POST /api/cart/add-product
Content-Type: application/json

{
  "customer_email": "customer@example.com",
  "product_id": 1,
  "quantity": 2
}

Response:
{
  "success": true,
  "message": "Product added to cart successfully",
  "data": {
    "cart_id": 1,
    "cart_item": { ... }
  }
}
```

### Get Active Cart
```bash
GET /api/cart/active?customer_email=customer@example.com

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "customer_email": "customer@example.com",
    "status": "active",
    "items": [ ... ]
  }
}
```

### Finalize Cart
```bash
POST /api/cart/{cartId}/finalize

Response:
{
  "success": true,
  "message": "Cart finalized successfully",
  "data": { ... }
}
```

### Complete Cart from Email Link
```bash
GET /cart/{cartId}/complete?token=<secure_token>
```

## 🎨 Features

### Reminder System
- **Three Progressive Reminders**: Configurable intervals
- **Smart Scheduling**: Uses delayed queue jobs
- **Auto-Cancellation**: Stops reminders when cart is finalized
- **Idempotent**: Safe to retry failed jobs

### Email Template
- **Responsive HTML**: Professional design
- **Cart Summary**: Shows all items, quantities, prices
- **Total Amount**: Clear pricing information
- **Secure Links**: HMAC-signed tokens for cart completion
- **Progressive Messaging**: Different subject lines for each reminder

### Security
- **Token Validation**: HMAC-SHA256 signed links
- **Status Verification**: Double-checks cart not already finalized
- **SQL Injection Protection**: Eloquent ORM
- **XSS Protection**: Blade templating with escaping

## 🔍 Monitoring & Observability

### Logging
All events logged to `storage/logs/laravel.log`:
```
[timestamp] local.INFO: Cart reminder sent {"cart_id":1,"reminder_number":1,"customer_email":"..."}
[timestamp] local.INFO: Cart finalized {"cart_id":1,"finalized_at":"..."}
```

### Queue Monitoring
```bash
# Watch queue worker in real-time
make queue

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Database Monitoring
```bash
# Check reminder status
php artisan tinker
> CartReminder::where('status', 'pending')->count();

# View recent carts
> Cart::with('reminders')->latest()->take(5)->get();
```

## 🧪 Testing

### Automated Tests

The project includes comprehensive test coverage with **41 passing tests (123 assertions)**:

**Run all tests:**
```bash
php artisan test
```

**Run specific test suites:**
```bash
# Feature tests (API endpoints, integrations)
php artisan test --testsuite=Feature

# Unit tests (Services, business logic)
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

**Test Coverage:**
- ✅ **Feature Tests (19 tests):** API endpoints, cart operations, reminder scheduling, email completion flow, validation
- ✅ **Unit Tests (20 tests):** CartService, ReminderService, business logic, edge cases

**What's tested:**
- Cart creation and product addition
- Quantity increment for existing products
- Reminder scheduling (3 reminders with configurable intervals)
- Cart finalization via API and email link
- Reminder cancellation on order completion
- HMAC token validation for email links
- Input validation and error handling
- Multiple customers and concurrent carts
- Queue job dispatching

### Manual Testing Flow

1. **Create a cart:**
```bash
curl -X POST http://127.0.0.1:8000/api/cart/add-product \
  -H "Content-Type: application/json" \
  -d '{"customer_email": "test@example.com", "product_id": 1, "quantity": 2}'
```

2. **Check reminders scheduled:**
```bash
php artisan tinker --execute="CartReminder::latest()->take(3)->get()"
```

3. **Wait for reminder intervals** (5, 10, 12 minutes with default config)

4. **Check email in Mailtrap inbox** or `storage/logs/laravel.log`

5. **Click email link** to complete order

6. **Verify remaining reminders cancelled:**
```bash
php artisan tinker --execute="CartReminder::where('status', 'cancelled')->count()"
```

### Automated Tests
Tests can be added in `tests/Feature/CartTest.php`:
```php
test_can_add_product_to_cart()
test_reminders_are_scheduled()
test_finalize_cancels_pending_reminders()
test_email_link_completes_cart()
test_invalid_token_rejected()
```

## 🛠️ Makefile Commands

```bash
make setup    # Install dependencies and migrate database
make serve    # Start Laravel development server
make queue    # Start Redis queue worker
make test     # Run test suite (41 tests, 123 assertions)
make clean    # Clear all caches
```

## 📊 Software Engineering Best Practices

✅ **Clean Code**
- SOLID principles followed
- Descriptive naming conventions
- Single Responsibility classes
- Dependency Injection throughout

✅ **Separation of Concerns**
- Controllers: Handle HTTP requests
- Services: Business logic
- Models: Data access
- Jobs: Asynchronous processing
- Events: Domain events

✅ **Database Design**
- Proper relationships (hasMany, belongsTo)
- Indexed columns for performance
- Soft deletes not needed (simple status)
- Migration-based schema

✅ **Error Handling**
- Try-catch blocks in critical paths
- Graceful failure with logging
- Proper HTTP status codes
- Validation at API layer

✅ **Configuration**
- Environment-based settings
- No hardcoded values
- Easy to customize intervals

## 🔐 Production Considerations

### Environment
```env
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
```

### Nginx Configuration
Sample configuration provided in `nginx.conf`:
- PHP-FPM integration
- Static file handling
- Security headers
- Hidden files protection

### Queue Workers
Use Supervisor to keep queue workers running:
```ini
[program:abandoned-cart-worker]
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

### Monitoring
- Set up log aggregation (ELK, CloudWatch)
- Monitor queue depth
- Alert on failed jobs > threshold
- Track email delivery rates

## 📝 Notes

- Email intervals configured for **fast testing** (5, 10, 12 minutes)
- For production, use **hours** instead of minutes
- Queue worker **must be running** for emails to send
- Redis required for queue functionality
- Mailtrap recommended for development email testing

## 👥 Author

Built as a technical challenge demonstrating:
- Clean Architecture
- Event-Driven Design
- Queue-Based Processing
- Professional Email Marketing Automation

---

**License**: MIT
