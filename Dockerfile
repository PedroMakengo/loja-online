# =====================================================================
# DIOTEKNO — imagem de produção (Coolify / Docker)
# PHP 8.2 + Apache, com o CSS do Tailwind compilado na build.
# =====================================================================

# ---- 1) Compilar o CSS (Tailwind) -----------------------------------
FROM node:20-alpine AS css
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY tailwind.config.js ./
COPY src ./src
COPY app ./app
COPY assets ./assets
COPY core ./core
RUN npm run css

# ---- 2) PHP + Apache ------------------------------------------------
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql opcache \
 && a2enmod rewrite headers expires deflate remoteip \
 # O .htaccess precisa de AllowOverride All
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-diotekno.ini
COPY docker/apache-proxy.conf /etc/apache2/conf-enabled/zz-proxy.conf
COPY docker/entrypoint.sh /usr/local/bin/diotekno-entrypoint
RUN chmod +x /usr/local/bin/diotekno-entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=css --chown=www-data:www-data /app/assets/css/app.css ./assets/css/app.css

RUN mkdir -p storage/logs storage/comprovativos assets/img/produtos/uploads assets/img/blog/uploads \
 && chown -R www-data:www-data storage assets/img

ENV AMBIENTE=prod

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
  CMD curl -fsS -o /dev/null http://localhost/ || exit 1

ENTRYPOINT ["diotekno-entrypoint"]
CMD ["apache2-foreground"]
