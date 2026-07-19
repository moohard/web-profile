import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AdminLayout from '@/layouts/admin-layout';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) =>
            // Opsi { import: 'default' } membuat glob mengembalikan komponen
            // (bukan module namespace), sehingga tipe kembalian resolver cocok
            // dengan ComponentResolver SSR Inertia v3 (Promise<ResolvedComponent>).
            resolvePageComponent<ResolvedComponent>(
                `./pages/${name}.tsx`,
                import.meta.glob<ResolvedComponent>('./pages/**/*.tsx', {
                    import: 'default',
                }),
            ),
        layout: (name) => {
            switch (true) {
                case name === 'welcome':
                    return null;
                // Halaman publik memakai PublicLayout di dalam page component
                case name.startsWith('public/'):
                    return null;
                case name.startsWith('auth/'):
                    return AuthLayout;
                case name.startsWith('admin/'):
                    return AdminLayout;
                case name.startsWith('settings/'):
                    return [AppLayout, SettingsLayout];
                default:
                    return AppLayout;
            }
        },
        // TooltipProvider dan Toaster dibungkus di sini karena varian SSR
        // tidak mendukung opsi withApp seperti pada entry app.tsx (client-only).
        setup: ({ App, props }) => (
            <TooltipProvider delayDuration={0}>
                <App {...props} />
                <Toaster />
            </TooltipProvider>
        ),
    }),
);
