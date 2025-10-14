# Image officielle PHP avec Apache
FROM php:8.2-apache

# Copie des fichiers du projet dans le dossier web d’Apache
COPY . /var/www/html/

# Active les extensions nécessaires (par exemple MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Expose le port 80
EXPOSE 80
