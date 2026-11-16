# Railway / Docker image — same Apache + .htaccess setup as BigRock cPanel hosting
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql gd \
 && (a2dismod -f mpm_event mpm_worker || true) && a2enmod mpm_prefork rewrite expires headers \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && rm -rf /var/lib/apt/lists/*

RUN { echo 'upload_max_filesize=16M'; echo 'post_max_size=64M'; echo 'memory_limit=256M'; echo 'expose_php=Off'; } > /usr/local/etc/php/conf.d/anant.ini \
 && echo 'ServerTokens Prod' >> /etc/apache2/apache2.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
CMD ["/usr/local/bin/start.sh"]
