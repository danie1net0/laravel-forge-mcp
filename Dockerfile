FROM php:8.4-cli-alpine

LABEL maintainer="Daniel Neto"
LABEL description="Laravel Forge MCP Server"

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    && rm -rf /var/cache/apk/*

# Configure PHP for MCP (disable ALL output to stdout)
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/mcp.ini && \
    echo "display_startup_errors = Off" >> /usr/local/etc/php/conf.d/mcp.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/mcp.ini && \
    echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/mcp.ini && \
    echo "error_reporting = 0" >> /usr/local/etc/php/conf.d/mcp.ini && \
    echo "opcache.enable = 1" >> /usr/local/etc/php/conf.d/mcp.ini && \
    echo "opcache.enable_cli = 0" >> /usr/local/etc/php/conf.d/mcp.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies (with caching)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# Copy application code
COPY . .

# Run post-install scripts
RUN composer dump-autoload --optimize

# Create .env from example if not exists
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Generate application key if not set
RUN php artisan key:generate --ansi --force

# Set permissions
RUN chown -R www-data:www-data /app

# Switch to non-root user for security
USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php artisan --version || exit 1

# Environment for MCP (no stdout pollution)
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV LOG_LEVEL=error

# Run MCP server on stdin/stdout (stderr for any errors)
CMD ["php", "-d", "display_errors=Off", "-d", "error_reporting=0", "artisan", "mcp:start", "forge"]
