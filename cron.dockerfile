FROM php:8.1

RUN apt-get update -y
RUN apt-get install -y cron
RUN apt-get install -y wget
RUN apt-get install -y unzip libpq-dev libcurl4-gnutls-dev
RUN apt-get install -y libpng-dev
RUN apt-get install -y build-essential chrpath libssl-dev libxft-dev
RUN apt-get install -y libfreetype6 libfreetype6-dev libfontconfig1 libfontconfig1-dev 
RUN apt-get install -y nano
# XVFB Libraries
RUN apt-get install -y xvfb libfontconfig wkhtmltopdf


RUN docker-php-ext-install mysqli
RUN docker-php-ext-install gd


RUN apt-get update && \
    apt-get install -y libxslt1-dev && \
    docker-php-ext-install xsl && \
    apt-get remove -y libxslt1-dev icu-devtools libicu-dev libxml2-dev && \
    rm -rf /var/lib/apt/lists/*


COPY . .
#COPY --from=composer:2.3.5 /usr/bin/composer /usr/bin/composer

# FhantomJS SETUP
COPY /phantomjs /usr/local/share/phantomjs
RUN ["ln", "-sf", "/usr/local/share/phantomjs/bin/phantomjs", "/usr/local/bin"]

# Copy crontab file to the cron.d directory
COPY crontab /etc/cron.d/crontab

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/crontab

# Apply cron job
RUN crontab /etc/cron.d/crontab

# Create the log file to be able to run tail
RUN touch /var/log/cron.log

#ENV OPENSSL_CONF=/dev/null

CMD cron && tail -f /var/log/cron.log