FROM php:8.2-apache

# ── PHP extensions ─────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Apache config: DocumentRoot → /var/www/html/src ───────────────────────────
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/src|g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|<Directory /var/www/html>|<Directory /var/www/html/src>|g' \
        /etc/apache2/apache2.conf 2>/dev/null || true

# ── Copy project files ─────────────────────────────────────────────────────────
COPY .env   /var/www/html/.env
COPY src/   /var/www/html/src/

# ── Permissions ────────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80