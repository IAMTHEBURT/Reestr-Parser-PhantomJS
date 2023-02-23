FROM php:8.1-fpm as php
#FROM php:7.4-fpm as php
RUN apt-get update -y
RUN apt-get install -y nano
RUN docker-php-ext-install mysqli

# XVFB Libraries
RUN apt install -y xvfb libfontconfig wkhtmltopdf

# Install XSL
RUN apt-get install -y --force-yes libxslt-dev
RUN docker-php-ext-install xsl


#xvfb xvfb-run


# Install xmlrpc
#RUN docker-php-ext-install xmlrpc