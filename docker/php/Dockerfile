FROM php:8.2-fpm-alpine

# Настройка часового пояса (раньше для лучшего кэширования)
ENV TZ=Europe/Moscow

# Устанавливаем системные зависимости и расширения PHP в одном слое
# Это уменьшает количество слоев и улучшает кэширование
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
    && apk add --no-cache \
        postgresql-libs \
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
        tzdata \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        bcmath \
    && apk del .build-deps \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем пользователя для приложения
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Настраиваем рабочую директорию
WORKDIR /var/www

# Копируем только composer файлы для кэширования зависимостей
COPY composer.json composer.lock* ./

# Устанавливаем зависимости с кэшированием (без dev-зависимостей для production)
# Используем --prefer-dist для быстрой установки из архивов
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist --no-scripts

# Копируем исходный код (это делается после установки зависимостей для лучшего кэширования)
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