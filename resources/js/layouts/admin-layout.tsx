import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

/**
 * Shell layout area admin: sidebar + topbar dari starter,
 * ditambah skip-to-content dan region konten utama.
 */
export default function AdminLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <a
                href="#admin-main"
                className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:text-black"
            >
                Lewati ke konten utama
            </a>
            {/* div (bukan main) — SidebarInset di AppContent sudah merender <main> */}
            <div id="admin-main" tabIndex={-1}>
                {children}
            </div>
        </AppSidebarLayout>
    );
}
