FROM php:8.3-apache

ARG GLPI_VERSION=11.0.11
ARG USERNAME=dev
ARG USER_UID=1000
ARG USER_GID=1000

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      ca-certificates curl gettext git mariadb-client sudo unzip \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions required or recommended by GLPI, plus Xdebug
RUN install-php-extensions apcu bcmath bz2 exif gd intl ldap mysqli opcache zip xdebug

# Apache: DocumentRoot is /public, everything else is rewritten to index.php (GLPI 11)
RUN a2enmod rewrite
COPY apache-glpi.conf /etc/apache2/sites-available/000-default.conf
COPY php-dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

# GLPI with config, files and logs moved to /var/lib/glpi and /var/log/glpi (volumes)
RUN curl -fsSL "https://github.com/glpi-project/glpi/releases/download/${GLPI_VERSION}/glpi-${GLPI_VERSION}.tgz" \
      | tar -xz -C /var/www \
 && mkdir -p /var/lib/glpi/config /var/lib/glpi/files /var/log/glpi \
 && cp -a /var/www/glpi/files/. /var/lib/glpi/files/ \
 && chown -R www-data:www-data /var/lib/glpi /var/log/glpi
COPY downstream.php /var/www/glpi/inc/downstream.php

# Developer user with passwordless sudo, member of www-data
RUN groupadd --gid ${USER_GID} ${USERNAME} \
 && useradd --uid ${USER_UID} --gid ${USER_GID} --groups www-data --create-home --shell /bin/bash ${USERNAME} \
 && echo "${USERNAME} ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/${USERNAME} \
 && chmod 0440 /etc/sudoers.d/${USERNAME}

WORKDIR /var/www/glpi
