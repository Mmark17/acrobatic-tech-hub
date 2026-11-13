FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mysqli mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/html/uploads/profile

COPY makangaweb/*.php /var/www/html/
COPY makangaweb/includes/ /var/www/html/includes/
COPY makangaweb/css/ /var/www/html/css/
COPY makangaweb/js/ /var/www/html/js/
COPY makangaweb/images/ /var/www/html/images/
COPY makangaweb/uploads/profile/.htaccess /var/www/html/uploads/profile/.htaccess
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/vercel-entrypoint

RUN chmod +x /usr/local/bin/vercel-entrypoint \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/vercel-entrypoint"]
