FROM php:8.2-fpm-alpine

# Устанавливаем системные зависимости
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    nodejs \
    npm \
    supervisor \
    tzdata

# Устанавливаем расширения PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    bcmath

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка часового пояса
ENV TZ=Europe/Moscow
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Создаем пользователя для приложения
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Настраиваем рабочую директорию
WORKDIR /var/www

# Копируем composer.json (composer.lock может отсутствовать)
COPY composer.json ./

# Устанавливаем зависимости (без dev-зависимостей для production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Копируем исходный код
COPY . .

# Настраиваем права доступа
RUN chown -R www:www /var/www \
    && chmod -R 755 /var/www/public \
    && mkdir -p /var/www/public/uploads \
    && mkdir -p /var/www/logs \
    && chmod -R 775 /var/www/public/uploads \
    && chmod -R 775 /var/www/logs

# Переключаемся на пользователя www
USER www

# Открываем порт для PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]