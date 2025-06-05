 # Laravel Envoy Deployment Configuration

This project uses Laravel Envoy for automated deployment and maintenance tasks. The configuration includes comprehensive deployment workflows for both development and production environments.

## Prerequisites

1. **Laravel Envoy Installation**: Already installed via Composer
2. **SSH Access**: Configure SSH keys for production server access
3. **Environment Variables**: Set up `.env` file with deployment configurations
4. **Slack Notifications** (Optional): Add `SLACK_WEBHOOK_URL` to `.env` for deployment notifications

## Server Configuration

Update the server configuration in `Envoy.blade.php`:

```blade
@servers(['local' => 'localhost', 'production' => 'your-production-server.com'])
```

## Available Commands

### 🚀 Deployment Tasks

#### Development Deployment

```bash
# Complete development deployment with optimizations
vendor/bin/envoy run deploy-dev
```

-   Pulls latest changes from git
-   Updates Composer dependencies
-   Runs database migrations
-   Seeds views
-   Optimizes cache (config, routes, views)

#### Safe Development Deployment

```bash
# Safe deployment with backup for development
vendor/bin/envoy run deploy-safe
```

-   Creates database backup before deployment
-   Runs complete development deployment
-   Includes health checks

#### Production Deployment

```bash
# Full production deployment with maintenance mode
vendor/bin/envoy run deploy-production
```

-   Enables maintenance mode
-   Creates database backup
-   Pulls latest changes
-   Updates dependencies
-   Runs migrations and seeding
-   Optimizes all caches for production
-   Restarts queue workers
-   Runs health checks
-   Disables maintenance mode

#### Quick Fix Deployment

```bash
# Quick deployment for minor fixes
vendor/bin/envoy run quick-fix
```

-   For small hotfixes without database changes
-   Skips migrations and heavy optimizations

### 🗄️ Database Tasks

#### Run Migrations

```bash
vendor/bin/envoy run migrate
```

#### Seed Views

```bash
vendor/bin/envoy run seed-views
```

#### Create Backup

```bash
vendor/bin/envoy run backup
```

#### Rollback Migrations

```bash
vendor/bin/envoy run rollback
```

### 🚀 Optimization Tasks

#### Cache Optimization

```bash
vendor/bin/envoy run optimize
```

-   Clears existing caches
-   Rebuilds config, route, view, and event caches

#### Clear All Caches

```bash
vendor/bin/envoy run clear-cache
```

-   Clears all application caches without rebuilding

#### Update Dependencies

```bash
vendor/bin/envoy run update-deps
```

-   Updates Composer packages
-   Updates NPM packages and rebuilds assets

### 🔧 Maintenance Tasks

#### Enable Maintenance Mode

```bash
vendor/bin/envoy run maintenance-on
```

#### Disable Maintenance Mode

```bash
vendor/bin/envoy run maintenance-off
```

#### Restart Queue Workers

```bash
vendor/bin/envoy run queue-restart
```

### 🏥 Monitoring Tasks

#### Health Check

```bash
vendor/bin/envoy run health-check
```

-   Runs system health checks using Spatie Health package

#### Status Check

```bash
vendor/bin/envoy run status-check
```

-   Shows comprehensive application status and health information

### 📖 Deployment Stories (Complex Workflows)

#### Complete Production Deployment

```bash
vendor/bin/envoy run deploy-production
```

**Single comprehensive production deployment task**

#### Safe Production Deployment with Manual Control

```bash
vendor/bin/envoy run safe-production-deploy
```

**Workflow:**

1. Enables maintenance mode
2. Creates backup
3. Runs production deployment
4. Disables maintenance mode

#### Development Deployment with Health Check

```bash
vendor/bin/envoy run deploy-development
```

**Workflow:**

1. Runs migrations
2. Seeds views
3. Optimizes caches
4. Performs health check

#### Full System Update

```bash
vendor/bin/envoy run full-update
```

**Workflow:**

1. Updates all dependencies
2. Runs migrations
3. Seeds views
4. Optimizes caches
5. Performs health check

#### Emergency Rollback

```bash
vendor/bin/envoy run emergency-rollback
```

**Workflow:**

1. Enables maintenance mode
2. Rolls back last migration
3. Clears all caches
4. Disables maintenance mode

#### Maintenance Window Deployment

```bash
vendor/bin/envoy run maintenance-deploy
```

**Workflow:**

1. Enables maintenance mode
2. Runs development deployment
3. Restarts queue workers
4. Disables maintenance mode

#### Complete System Health Check

```bash
vendor/bin/envoy run complete-system-check
```

**Workflow:**

1. Shows application status
2. Runs health checks
3. Creates backup for safety

## Environment Configuration

### Required Environment Variables

Add these to your `.env` file:

```env
# Deployment Configuration
DEPLOYMENT_BRANCH=main
BACKUP_ENABLED=true

# Slack Notifications (Optional)
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
```

### Production Server Setup

1. **SSH Key Configuration**:

    ```bash
    # Generate SSH key if not exists
    ssh-keygen -t rsa -b 4096 -C "your-email@example.com"

    # Copy public key to production server
    ssh-copy-id user@your-production-server.com
    ```

2. **Server Directory Structure**:
    ```
    /var/www/
    ├── project_manager/          # Main application directory
    ├── releases/                 # Release directories (for zero-downtime deployments)
    └── backups/                  # Database and file backups
    ```

## Monitoring and Notifications

### Slack Integration

The deployment system includes automatic Slack notifications for:

-   ✅ Successful deployments
-   ❌ Failed deployments
-   📊 Deployment status updates

Configure your Slack webhook URL in the `.env` file to enable notifications.

### Health Monitoring

After each deployment, the system automatically runs health checks to ensure:

-   Database connectivity
-   Cache functionality
-   Queue systems
-   External service connections

## Best Practices

### Development Workflow

1. Test changes locally
2. Run `vendor/bin/envoy run deploy-development`
3. Verify health checks pass

### Production Workflow

1. Test in development environment
2. Run `vendor/bin/envoy run deploy-production`
3. Monitor application post-deployment
4. Check health dashboard at `/system/health`

### Maintenance Tasks

-   **Daily**: Automated backups via Laravel scheduler
-   **Weekly**: Run `vendor/bin/envoy run optimize` for cache optimization
-   **Monthly**: Run `vendor/bin/envoy run update-deps` for dependency updates

## Troubleshooting

### Common Issues

1. **SSH Connection Failed**:

    ```bash
    # Test SSH connection
    ssh user@your-production-server.com
    ```

2. **Permission Denied**:

    ```bash
    # Fix Laravel storage permissions
    sudo chown -R www-data:www-data storage/
    sudo chmod -R 775 storage/
    ```

3. **Migration Errors**:

    ```bash
    # Check database connection
    php artisan tinker
    DB::connection()->getPdo();
    ```

4. **Cache Issues**:
    ```bash
    # Clear all caches manually
    vendor/bin/envoy run clear-cache
    ```

### Rollback Procedure

If deployment fails:

1. **Immediate Rollback**:

    ```bash
    vendor/bin/envoy run rollback
    ```

2. **Restore from Backup** (if needed):

    ```bash
    # Restore database from latest backup
    php artisan backup:restore --latest
    ```

3. **Clear Caches**:
    ```bash
    vendor/bin/envoy run clear-cache
    ```

## Security Considerations

-   Use SSH keys instead of passwords
-   Restrict server access to deployment user
-   Regularly update server packages
-   Monitor deployment logs
-   Use HTTPS for all communications
-   Keep backup files secure and encrypted

## Integration with Laravel Scheduler

The deployment system works seamlessly with Laravel's task scheduler for:

-   Automated health checks
-   Scheduled backups
-   Cache warming
-   Log rotation

Ensure your cron job is configured:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

# Production deployment

vendor/bin/envoy run deploy-prod

# Quick update (git pull + optimize)

vendor/bin/envoy run quick-update

# Safe deployment with backup

vendor/bin/envoy run deploy-safe

````

### Database Tasks

```bash
# Run migrations only
vendor/bin/envoy run migrate

# Seed views only
vendor/bin/envoy run seed-views

# Both migration and seeding
vendor/bin/envoy run migrate seed-views
````

### Cache Management

```bash
# Optimize application (cache config, routes, views)
vendor/bin/envoy run optimize

# Clear all caches
vendor/bin/envoy run clear-cache
```

### Utility Tasks

```bash
# Run health checks
vendor/bin/envoy run health-check

# Create database backup
vendor/bin/envoy run backup

# Enable maintenance mode
vendor/bin/envoy run maintenance-on

# Disable maintenance mode
vendor/bin/envoy run maintenance-off

# Rollback (production only)
vendor/bin/envoy run rollback
```

### Story Workflows (Multiple Tasks)

```bash
# Standard deployment story
vendor/bin/envoy run deploy

# Full deployment with maintenance mode
vendor/bin/envoy run full-deploy

# Quick deployment
vendor/bin/envoy run quick-deploy
```

## Configuration

### For Production Deployment

1. Update the server configuration in `Envoy.blade.php`:

    ```php
    @servers(['production' => 'user@your-server.com'])
    ```

2. Update the repository URL:

    ```php
    $repository = 'git@github.com:your-username/project_manager.git';
    ```

3. Update deployment paths:
    ```php
    $releases_dir = '/var/www/releases';
    $app_dir = '/var/www/project_manager';
    ```

### For Slack Notifications

Replace `webhook-url` with your actual Slack webhook URL in the notification sections.

## SSH Key Setup

For production deployments, ensure your SSH key is added to the server:

```bash
# Copy your public key to the server
ssh-copy-id user@your-server.com

# Test connection
ssh user@your-server.com
```

## Usage Examples

### Daily Development Workflow

```bash
# Quick update and optimize
vendor/bin/envoy run quick-update

# After making database changes
vendor/bin/envoy run migrate seed-views
```

### Production Deployment

```bash
# Safe deployment with backup
vendor/bin/envoy run deploy-safe

# Or use the full story
vendor/bin/envoy run full-deploy
```

### Emergency Situations

```bash
# Enable maintenance mode
vendor/bin/envoy run maintenance-on

# Rollback to previous version
vendor/bin/envoy run rollback

# Disable maintenance mode
vendor/bin/envoy run maintenance-off
```

## Quick Reference Guide

### 🚨 Emergency Procedures

#### 1. Emergency Rollback

```bash
# If something goes wrong after deployment
vendor/bin/envoy run emergency-rollback
```

#### 2. Quick Hotfix

```bash
# For urgent bug fixes
vendor/bin/envoy run quick-fix
```

#### 3. System Health Check

```bash
# When something seems wrong
vendor/bin/envoy run complete-system-check
```

### 📋 Daily Operations

#### Morning Deployment Routine

```bash
# 1. Check system health
vendor/bin/envoy run health-check

# 2. Deploy development changes
vendor/bin/envoy run deploy-development

# 3. Verify everything is working
vendor/bin/envoy run status-check
```

#### End-of-Day Backup

```bash
# Create a backup before leaving
vendor/bin/envoy run backup
```

### 🔄 Weekly Maintenance

#### System Update Routine

```bash
# 1. Full system update (Fridays)
vendor/bin/envoy run full-update

# 2. Complete health check
vendor/bin/envoy run complete-system-check
```

### 🎯 Production Deployment Checklist

1. **Pre-deployment:**

    ```bash
    vendor/bin/envoy run complete-system-check
    ```

2. **Deployment:**

    ```bash
    vendor/bin/envoy run safe-production-deploy
    ```

3. **Post-deployment:**

    ```bash
    vendor/bin/envoy run health-check
    ```

4. **If issues arise:**
    ```bash
    vendor/bin/envoy run emergency-rollback
    ```

### ⚙️ Environment-Specific Notes

-   **Development**: Use `deploy-dev` or `deploy-development` story
-   **Staging**: Use `deploy-safe` with manual testing
-   **Production**: Always use `safe-production-deploy` story

### 🔧 Maintenance Commands

```bash
# Enable maintenance mode for manual work
vendor/bin/envoy run maintenance-on

# Your manual work here...

# Disable maintenance mode when done
vendor/bin/envoy run maintenance-off
```

## Summary

Your Laravel Envoy deployment system is now fully configured with:

### ✅ Completed Setup

1. **Laravel Envoy Package**: Installed and ready
2. **Comprehensive Task Library**: 16+ deployment tasks available
3. **Deployment Stories**: 7 workflow combinations for different scenarios
4. **Health Monitoring Integration**: Connected with Spatie Health package
5. **Backup Integration**: Connected with Laravel Backup package
6. **Status Monitoring Script**: `deployment-status.sh` for quick system overview

### 🎯 Key Features

-   **Environment-Aware Deployments**: Different strategies for dev/staging/production
-   **Safety First**: Automatic backups before production deployments
-   **Maintenance Mode**: Automatic handling during deployments
-   **Health Monitoring**: Integrated health checks after deployments
-   **Emergency Procedures**: Quick rollback and hotfix capabilities
-   **Cache Management**: Comprehensive optimization commands
-   **Queue Management**: Worker restart capabilities

### 🚀 Getting Started

1. **Daily Development**: `vendor/bin/envoy run deploy-development`
2. **Production Deployment**: `vendor/bin/envoy run safe-production-deploy`
3. **System Status**: `./deployment-status.sh`
4. **Emergency Response**: `vendor/bin/envoy run emergency-rollback`

### 📞 Support

-   Full documentation in this file
-   Task list: `vendor/bin/envoy task`
-   System status: `./deployment-status.sh`
-   Health monitoring: Available at `/system/health` in your application

The deployment automation system is production-ready and includes all best practices for Laravel application deployment.
