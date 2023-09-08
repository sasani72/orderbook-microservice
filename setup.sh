#!/bin/bash

# Navigate to the Laravel project directory
cd /usr/src/app

if [ ! -f .env ]; then
    cp .env.example .env
else
    echo ".env file already exists, skipping creation."
fi

# Install Composer dependencies
composer install

# Run database migrations
php artisan migrate