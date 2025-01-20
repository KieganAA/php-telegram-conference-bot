server:
	@echo "Starting local PHP server..."
	php -S localhost:8080 -t public

tunnel:
	@echo "Starting ngrok..."
	ngrok http 8080

webhook:
	@echo "Setting Telegram webhook..."
	curl -F "url=https://aio-conference-bot.com/index.php" \
        https://api.telegram.org/bot7617129159:AAHTAYx2cQv_Gly5X0pY-Z0_WbmBAW1yBmY/setWebhook

.PHONY: restart-server
restart-server:
	sudo systemctl restart nginx
	sudo systemctl restart mysql

logs:
	sudo tail -f /var/log/nginx/error.log
