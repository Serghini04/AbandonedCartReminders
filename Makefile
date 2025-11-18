.PHONY: help setup serve nginx nginx-config nginx-restart queue test clean metrics metrics-watch

help:
	@echo "Abandoned Cart Reminders - Commands"
	@echo "-----------------------------------"
	@echo "  make setup          - Install dependencies & init app"
	@echo "  make serve          - Run Laravel (php artisan serve)"
	@echo "  make nginx          - Configure + restart Nginx (first time only)"
	@echo "  make nginx-config   - Configure Nginx once"
	@echo "  make nginx-restart  - Restart Nginx + PHP-FPM"
	@echo "  make queue          - Start queue worker"
	@echo "  make metrics        - View cart metrics"
	@echo "  make metrics-watch  - Watch metrics in real-time"
	@echo "  make test           - Run tests"
	@echo "  make clean          - Clear Laravel caches"

setup:
	@echo "Installing dependencies..."
	composer install

	@if [ ! -f .env ]; then \
		echo "Creating .env file..."; \
		cp .env.example .env; \
	fi
	
	php artisan key:generate
	php artisan migrate
	@echo "Setup complete!"

serve:
	php artisan serve

nginx-config:
	@echo "Copying Nginx configuration..."
	@sudo cp ./nginx.conf /etc/nginx/sites-available/abandoned-cart

	@echo "Enabling site..."
	@sudo ln -sf /etc/nginx/sites-available/abandoned-cart /etc/nginx/sites-enabled/abandoned-cart

	@echo "Updating project root path..."
	@sudo sed -i "s|/var/www/AbandonedCartReminders|$(PWD)|g" /etc/nginx/sites-available/abandoned-cart

	@echo "Fixing Laravel permissions..."
	@sudo chmod -R 775 storage bootstrap/cache
	@sudo chown -R serghini:www-data storage bootstrap/cache

	@echo "Testing Nginx configuration..."
	@sudo nginx -t && echo "✓ Nginx config OK"

nginx-restart:
	@echo "Restarting PHP-FPM..."
	@sudo systemctl restart php8.3-fpm 2>/dev/null || sudo systemctl restart php8.2-fpm

	@echo "Restarting Nginx..."
	@sudo systemctl restart nginx

	@echo "✓ Nginx running → http://localhost"

nginx: nginx-config nginx-restart

queue:
	php artisan queue:work redis

metrics:
	@php artisan cart:log-metrics
	@echo ""
	@echo "Latest Metrics:"
	@cat storage/logs/metrics-$$(date +%Y-%m-%d).log 2>/dev/null || echo "No metrics logged today"

metrics-watch:
	@echo "Watching metrics (Ctrl+C to stop)..."
	@tail -f storage/logs/metrics-$$(date +%Y-%m-%d).log

test:
	php artisan test

clean:
	php artisan optimize:clear
