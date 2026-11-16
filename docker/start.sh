#!/bin/sh
# Listen on Railway's $PORT and make sure the uploads volume is writable and protected.
set -e
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
mkdir -p /var/www/html/uploads
cat > /var/www/html/uploads/.htaccess <<'EOF'
Options -Indexes
<FilesMatch "\.(php|phtml|php\d|phar|pl|py|cgi|sh)$">
  Require all denied
</FilesMatch>
EOF
chown -R www-data:www-data /var/www/html/uploads
exec apache2-foreground
