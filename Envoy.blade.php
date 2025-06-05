@servers(['local' => '127.0.0.1', 'production' => 'your-production-server.com'])

@setup
    $repository = 'git@github.com:raheescv/laravel_project_manager.git';
    $releases_dir = '/var/www/releases';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@task('health-check', ['on' => 'local'])
    echo "🏥 Running health checks..."
    php artisan health:check
    echo "✅ Health check completed!"
@endtask

@task('migrate', ['on' => 'local'])
    echo "🔄 Running database migrations..."
    php artisan migrate --force
    echo "✅ Migrations completed!"
@endtask

@task('seed-views', ['on' => 'local'])
    echo "🌱 Seeding database with views..."
    php artisan db:seed --class=View --force
    echo "✅ View seeding completed!"
@endtask

@task('optimize', ['on' => 'local'])
    echo "🚀 Optimizing cache..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo "✅ Cache optimization completed!"
@endtask

@task('clear-cache', ['on' => 'local'])
    echo "🗑️ Clearing all caches..."
    php artisan optimize:clear
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    echo "✅ All caches cleared!"
@endtask

@task('backup', ['on' => 'local'])
    echo "💾 Creating backup..."
    php artisan backup:run --only-db
    echo "✅ Backup completed!"
@endtask

@task('deploy-dev', ['on' => 'local'])
    echo "🚀 Starting development deployment..."

    echo "📥 Pulling latest changes from git..."
    git pull origin main

    echo "📦 Installing/updating dependencies..."
    composer install --optimize-autoloader --no-dev

    echo "🔄 Running database migrations..."
    php artisan migrate --force

    echo "🌱 Seeding database with views..."
    php artisan db:seed --class=View --force

    echo "🚀 Optimizing cache..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "✅ Development deployment completed successfully!"
@endtask

@task('deploy-safe', ['on' => 'local'])
    echo "🛡️ Starting safe deployment with backup..."

    echo "📥 Pulling latest changes..."
    git pull origin main

    echo "📦 Installing dependencies..."
    composer install --optimize-autoloader --no-dev

    echo "🔄 Running migrations..."
    php artisan migrate --force

    echo "🌱 Seeding views..."
    php artisan db:seed --class=View --force

    echo "🚀 Optimizing cache..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "🏥 Running health check..."
    php artisan health:check

    echo "✅ Safe deployment completed!"
@endtask

@task('update-deps', ['on' => 'local'])
    echo "📦 Updating dependencies..."
    composer update --optimize-autoloader --no-dev
    npm install && npm run build
    echo "✅ Dependencies updated!"
@endtask

@task('rollback', ['on' => 'production'])
    echo "🔄 Rolling back database migrations..."
    php artisan migrate:rollback --step=1
    php artisan optimize:clear
    echo "✅ Rollback completed!"
@endtask

@task('maintenance-on', ['on' => 'local'])
    echo "🚧 Enabling maintenance mode..."
    php artisan down --render="errors::503" --retry=60
    echo "✅ Maintenance mode enabled!"
@endtask

@task('maintenance-off', ['on' => 'local'])
    echo "🟢 Disabling maintenance mode..."
    php artisan up
    echo "✅ Maintenance mode disabled!"
@endtask

@task('queue-restart', ['on' => 'local'])
    echo "🔄 Restarting queue workers..."
    php artisan queue:restart
    echo "✅ Queue workers restarted!"
@endtask

@task('deploy-production', ['on' => 'production'])
    echo "🚀 Starting production deployment..."

    echo "📥 Pulling latest changes..."
    git pull origin main

    echo "📦 Installing production dependencies..."
    composer install --optimize-autoloader --no-dev --no-interaction

    echo "🔄 Running migrations..."
    php artisan migrate --force

    echo "🌱 Seeding views..."
    php artisan db:seed --class=View --force

    echo "🚀 Optimizing for production..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

    echo "🔄 Restarting queue workers..."
    php artisan queue:restart

    echo "🏥 Running health check..."
    php artisan health:check

    echo "✅ Production deployment completed successfully!"
@endtask

@task('quick-fix', ['on' => 'local'])
    echo "⚡ Quick fix deployment..."
    git pull origin main
    composer install --optimize-autoloader --no-dev
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    echo "✅ Quick fix completed!"
@endtask

@task('status-check', ['on' => 'local'])
    echo "📊 Checking application status..."
    php artisan about
    php artisan health:check
    echo "✅ Status check completed!"
@endtask

@story('deploy-development')
    migrate
    seed-views
    optimize
    health-check
@endstory

@story('deploy-production')
    deploy-safe
    health-check
@endstory

@story('full-update')
    update-deps
    migrate
    seed-views
    optimize
    health-check
@endstory

@story('safe-production-deploy')
    deploy-production
@endstory

@story('emergency-rollback')
    maintenance-on
    rollback
    clear-cache
    maintenance-off
@endstory

@story('maintenance-deploy')
    maintenance-on
    deploy-dev
    queue-restart
    maintenance-off
@endstory

@story('complete-system-check')
    status-check
    health-check
    backup
@endstory
