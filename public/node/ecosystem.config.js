const path = require('path');

// Get the project root directory (two levels up from this file)
const projectRoot = path.resolve(__dirname, '../../');
const nodeDir = __dirname;
const logsDir = path.join(projectRoot, 'storage/logs');

module.exports = {
    apps: [{
        name: 'whatsapp-server',
        script: './server.js',
        cwd: nodeDir,
        instances: 1,
        autorestart: true,
        watch: false,
        max_memory_restart: '1G',
        env: {
            NODE_ENV: 'production',
            WHATSAPP_PORT: 3000,
            PROJECT_ROOT: projectRoot
        },
        error_file: path.join(logsDir, 'pm2-whatsapp-error.log'),
        out_file: path.join(logsDir, 'pm2-whatsapp-out.log'),
        log_file: path.join(logsDir, 'pm2-whatsapp-combined.log'),
        time: true,
        merge_logs: true,
        log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
        min_uptime: '10s',
        max_restarts: 10,
        restart_delay: 4000,
        kill_timeout: 5000,
        listen_timeout: 3000,
        exec_mode: 'fork'
    }]
};
