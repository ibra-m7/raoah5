import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devHost = env.VITE_DEV_HOST || 'localhost';

    return {
        plugins: [
            laravel({
                input: ['resources/js/admin.js', 'resources/js/marketing.js'],
                refresh: true,
            }),
        ],
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            hmr: {
                host: devHost,
            },
            origin: `http://${devHost}:5173`,
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
