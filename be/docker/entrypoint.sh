#!/bin/sh
set -e

echo "=========================================================="
echo "  BWS LEARNING INTEGRATED SYSTEM (LIS) - BACKEND CONTAINER"
echo "=========================================================="

# 1. Environment configuration
if [ ! -f .env ]; then
    if [ -f .env.docker.example ]; then
        echo "Creating .env from .env.docker.example..."
        cp .env.docker.example .env
    elif [ -f .env.example ]; then
        echo "Creating .env from .env.example..."
        cp .env.example .env
    fi
fi

# 1b. Ensure composer vendor dependencies are present
if [ ! -f vendor/autoload.php ]; then
    echo "Composer dependencies missing in volume. Running composer install..."
    composer install --no-interaction --prefer-dist
fi

# 1c. Clear any stale bootstrap configuration caches
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# 2. Ensure application key exists
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "Generating Laravel application key..."
    php artisan key:generate --force
fi

# 3. Create required runtime directories and set permissions
mkdir -p storage/framework/cache/data \
         storage/framework/cache/locks \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         bootstrap/cache

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 4. Wait for Database service (MySQL or PostgreSQL)
DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-mysql}
DB_PORT=${DB_PORT:-3306}
DB_USER=${DB_USERNAME:-bws_admin}
DB_PASS=${DB_PASSWORD:-bws_secret_pass}
DB_NAME=${DB_DATABASE:-bws_lis_db}

echo "Waiting for Database ($DB_CONNECTION at $DB_HOST:$DB_PORT) to become ready..."
RETRY=0
MAX_RETRIES=40

if [ "$DB_CONNECTION" = "mysql" ]; then
    until php -r "try { new PDO('mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME', '$DB_USER', '$DB_PASS'); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null || [ $RETRY -eq $MAX_RETRIES ]; do
        RETRY=$((RETRY+1))
        echo "MySQL is unavailable - waiting ($RETRY/$MAX_RETRIES)..."
        sleep 2
    done
else
    until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -q || [ $RETRY -eq $MAX_RETRIES ]; do
        RETRY=$((RETRY+1))
        echo "PostgreSQL is unavailable - waiting ($RETRY/$MAX_RETRIES)..."
        sleep 2
    done
fi

if [ $RETRY -eq $MAX_RETRIES ]; then
    echo "ERROR: Timed out waiting for database at $DB_HOST:$DB_PORT"
    exit 1
fi
echo "Database ($DB_CONNECTION) is ready and accepting connections."

# 5. Run Database Migrations
echo "Running database migrations..."
php artisan migrate --force

# 6. Automatic Database Seeding on Initial Startup
USER_COUNT=$(php artisan tinker --execute 'echo \App\Models\User::count();' 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ] || [ "${SEED_ON_START:-false}" = "true" ]; then
    echo "Fresh database detected. Seeding core RBAC, demo users & master data..."
    php artisan db:seed --force
    echo "Database seeding completed successfully."
else
    echo "Database already contains data ($USER_COUNT users). Skipping seed."
fi

echo "Backend initialization finished successfully."
echo "Starting application process: $@"
echo "=========================================================="

exec "$@"
