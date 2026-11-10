# Usa a imagem oficial do PHP 8.2 com Apache como base
FROM php:8.2-apache

# Define o fuso horário do container
ENV TZ=America/Sao_Paulo

# Instala as extensões PHP necessárias (PDO MySQL) e o Composer
RUN docker-php-ext-install pdo pdo_mysql && \
    a2enmod headers && \
    a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Configurar Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Configurar o código da aplicação
COPY . /var/www/html/

# Gera o autoload das classes (PSR-4)
RUN composer install --no-dev --optimize-autoloader

# Exponha a porta 80 para o servidor Apache
EXPOSE 80
