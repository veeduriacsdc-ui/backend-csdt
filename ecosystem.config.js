module.exports = {
    apps: [{
        name: 'csdt-backend',
        script: 'artisan',
        args: 'serve --host=0.0.0.0 --port=8000',
        instances: 1,
        exec_mode: 'fork',
        env: {
            NODE_ENV: 'development',
            APP_ENV: 'local'
        },
        env_production: {
            NODE_ENV: 'production',
            APP_ENV: 'production'
        },
        log_file: '/var/log/csdt-backend/combined.log',
        out_file: '/var/log/csdt-backend/out.log',
        error_file: '/var/log/csdt-backend/error.log',
        log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
        merge_logs: true,
        max_memory_restart: '512M',
        watch: false,
        ignore_watch: ['node_modules', 'storage/logs', 'vendor'],
        restart_delay: 4000,
        max_restarts: 10,
        min_uptime: '10s'
    }]
};
