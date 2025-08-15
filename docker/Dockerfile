FROM composer/composer:latest-bin AS composer

FROM php:8.3-cli-bookworm

# System packages

RUN apt-get update && apt-get install -y \
    unzip \
    # PostgreSQL
    libpq-dev \
    # MSSQL
    apt-transport-https \
    gnupg2 \
    libpng-dev \
    # Oracle
    libaio1 && \
    rm -rf /var/lib/apt/lists/*

# MSSQL dependencies

ENV ACCEPT_EULA=Y

# Install prerequisites for the sqlsrv and pdo_sqlsrv PHP extensions.
# Some packages are pinned with lower priority to prevent build issues due to package conflicts.
# Link: https://github.com/microsoft/linux-package-repositories/issues/39
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && echo "Package: unixodbc\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && echo "Package: unixodbc-dev\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && echo "Package: libodbc1:amd64\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && echo "Package: odbcinst\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && echo "Package: odbcinst1debian2:amd64\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && apt-get update \
    && apt-get install -y msodbcsql18 mssql-tools18 unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# Oracle dependencies
ARG TARGETARCH
RUN if [ "$TARGETARCH" = "arm64" ] ; then \
    cd /tmp && curl -L https://download.oracle.com/otn_software/linux/instantclient/2370000/instantclient-basic-linux.arm64-23.7.0.25.01.zip -O \
    && curl -L https://download.oracle.com/otn_software/linux/instantclient/2370000/instantclient-sdk-linux.arm64-23.7.0.25.01.zip -O \
    && curl -L https://download.oracle.com/otn_software/linux/instantclient/2370000/instantclient-sqlplus-linux.arm64-23.7.0.25.01.zip -O \
    && unzip -o /tmp/instantclient-basic-linux.arm64-23.7.0.25.01.zip -d /usr/local/ \
    && unzip -o /tmp/instantclient-sdk-linux.arm64-23.7.0.25.01.zip -d /usr/local/ \
    && unzip -o /tmp/instantclient-sqlplus-linux.arm64-23.7.0.25.01.zip -d /usr/local/ \
    && ln -s /usr/local/instantclient_23_7 /usr/local/instantclient; \
  elif [ "$TARGETARCH" = "amd64" ]; then \
    cd /tmp && curl -L https://download.oracle.com/otn_software/linux/instantclient/2350000/instantclient-basic-linux.x64-23.5.0.24.07.zip -O \
    && curl -L https://download.oracle.com/otn_software/linux/instantclient/2350000/instantclient-sdk-linux.x64-23.5.0.24.07.zip -O \
    && curl -L https://download.oracle.com/otn_software/linux/instantclient/2350000/instantclient-sqlplus-linux.x64-23.5.0.24.07.zip -O \
    && unzip /tmp/instantclient-basic-linux.x64-23.5.0.24.07.zip -d /usr/local/ \
    && unzip -o /tmp/instantclient-sdk-linux.x64-23.5.0.24.07.zip -d /usr/local/ \
    && unzip -o /tmp/instantclient-sqlplus-linux.x64-23.5.0.24.07.zip -d /usr/local/ \
    && ln -s /usr/local/instantclient_23_5 /usr/local/instantclient; \
  fi
RUN ln -s /usr/local/instantclient/lib* /usr/lib
RUN ln -s /usr/local/instantclient/sqlplus /usr/bin/sqlplus

RUN echo 'export LD_LIBRARY_PATH="/usr/local/instantclient"' >> /root/.bashrc
RUN echo 'umask 002' >> /root/.bashrc

# PHP extensions

RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    # For Psalm, to make use of JIT for a 20%+ performance boost.
    opcache

RUN pecl install sqlsrv
RUN printf "; priority=20\nextension=sqlsrv.so\n" > /usr/local/etc/php/conf.d/php-sqlsrv.ini

RUN pecl install pdo_sqlsrv
RUN printf "; priority=30\nextension=pdo_sqlsrv.so\n" > /usr/local/etc/php/conf.d/php-pdo-sqlsrv.ini

RUN echo 'instantclient,/usr/local/instantclient' | pecl install oci8
RUN echo "extension=oci8.so" > /usr/local/etc/php/conf.d/php-oci8.ini

RUN echo 'instantclient,/usr/local/instantclient' | pecl install pdo_oci
RUN echo "extension=pdo_oci.so" > /usr/local/etc/php/conf.d/php-pdo-oci.ini

# For code coverage (mutation testing)
RUN pecl install pcov && docker-php-ext-enable pcov

# Composer

COPY --from=composer /composer /usr/bin/composer

# Code

COPY . /code
WORKDIR /code

# PHP packages

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install
