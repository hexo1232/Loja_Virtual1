# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala a extensão mysqli para o PHP
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copia os ficheiros do teu projeto para a pasta do servidor
COPY . /var/www/html/

# Dá as permissões necessárias
RUN chown -R www-data:www-data /var/www/html

# O Render usa a porta 80 por padrão para imagens Apache, 
# mas podes expor explicitamente se necessário
EXPOSE 80

# O comando de inicialização já vem por padrão na imagem do PHP-Apache