# Start a local PHP development server
server-local:
	@echo "Starting local PHP server..."
	php -S localhost:8080 -t public

# Start ngrok for tunneling
tunnel:
	@echo "Starting ngrok..."
	ngrok http 8080

# Set the Telegram webhook
webhook:
	@echo "Setting Telegram webhook..."
	curl -F "url=https://aio-conference-bot.com/index.php" \
        https://api.telegram.org/bot7617129159:AAHTAYx2cQv_Gly5X0pY-Z0_WbmBAW1yBmY/setWebhook

# Clear Telegram webhook
webhook-clear:
	@echo "Clearing Telegram webhook..."
	curl -F "url=" https://api.telegram.org/bot7617129159:AAHTAYx2cQv_Gly5X0pY-Z0_WbmBAW1yBmY/deleteWebhook

# Get Telegram webhook information
webhook-info:
	@echo "Fetching Telegram webhook information..."
	curl https://api.telegram.org/bot7617129159:AAHTAYx2cQv_Gly5X0pY-Z0_WbmBAW1yBmY/getWebhookInfo | jq

# Restart essential services
.PHONY: restart
restart:
	@echo "Restarting services..."
	sudo systemctl restart nginx
	sudo systemctl restart mysql
	sudo systemctl restart php-fpm

# Tail Nginx error logs
logs:
	@echo "Tailing Nginx error logs..."
	sudo tail -f /var/log/nginx/error.log

# Tail Nginx access logs
access-logs:
	@echo "Tailing Nginx error logs..."
	sudo tail -f /var/log/nginx/access.log

# Clear Nginx logs
clear-logs:
	@echo "Clearing Nginx logs..."
	sudo truncate -s 0 /var/log/nginx/error.log
	sudo truncate -s 0 /var/log/nginx/access.log
	@echo "Logs cleared."

# Tail PHP-FPM logs
php-logs:
	@echo "Tailing PHP-FPM logs..."
	sudo journalctl -u php-fpm -f

# Clear PHP-FPM logs
clear-php-logs:
	@echo "Clearing PHP-FPM logs..."
	sudo truncate -s 0 /var/log/php-fpm/error.log
	@echo "PHP-FPM logs cleared."

# Clear cache (example for Laravel projects)
clear-cache:
	@echo "Clearing cache..."
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	@echo "Cache cleared."

# Show disk usage
disk-usage:
	@echo "Checking disk usage..."
	df -h

# Show memory usage
memory-usage:
	@echo "Checking memory usage..."
	free -h

# Check active services
services-status:
	@echo "Checking service statuses..."
	systemctl status nginx mysql php-fpm

# Stop all essential services
stop-services:
	@echo "Stopping services..."
	sudo systemctl stop nginx
	sudo systemctl stop mysql
	sudo systemctl stop php-fpm
	@echo "Services stopped."

# Start all essential services
start-services:
	@echo "Starting services..."
	sudo systemctl start nginx
	sudo systemctl start mysql
	sudo systemctl start php-fpm
	@echo "Services started."
