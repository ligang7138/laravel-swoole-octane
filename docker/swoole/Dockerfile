FROM php:8.4-cli-alpine

# 安装系统依赖（含构建工具）
RUN apk add --no-cache \
    linux-headers \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    openssl-dev \
    postgresql-dev \
    curl-dev \
    brotli-dev \
    c-ares-dev \
    zlib-dev \
    bash \
    procps \
    vim \
    $PHPIZE_DEPS

# 安装 PHP 扩展（docker-php-ext-install）
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    mysqli \
    gd \
    zip \
    pcntl \
    opcache \
    bcmath \
    intl \
    mbstring \
    xml \
    sockets

# 安装 Redis 扩展
RUN pecl channel-update pecl.php.net \
    && pecl install redis \
    && docker-php-ext-enable redis

# 安装 Swoole 扩展
RUN pecl install swoole-6.2.1 \
    --configure='--enable-sockets --enable-openssl --enable-mysqlnd --enable-swoole-curl --enable-cares --enable-brotli' \
    && docker-php-ext-enable swoole

# 清理构建依赖
RUN apk del $PHPIZE_DEPS

# 安装 Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# 启动脚本（解决 Alpine DNS 问题）
COPY docker/swoole/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 配置 PHP
RUN echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

EXPOSE 8000

CMD ["/usr/local/bin/entrypoint.sh"]
