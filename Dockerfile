FROM php:8.3-cli

RUN docker-php-ext-install pdo_mysql
WORKDIR /app

COPY "FrogeX(Frontend)/" /app/
COPY api/ /app/api/

EXPOSE 10000
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
