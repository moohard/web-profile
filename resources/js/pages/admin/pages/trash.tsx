import { Head, router } from '@inertiajs/react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';
import pagesRoutes, { index as pagesIndex } from '@/routes/admin/pages';

type PageSummary = {
    id: number;
    title: string;
    mode: string;
    status: string | null;
    updated_at: string;
    editUrl: string;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: { current_page: number; last_page: number };
};

export default function PagesTrash({
    pages,
    canManageTrash,
}: {
    pages: Paginated<PageSummary>;
    canManageTrash: boolean;
}) {
    function restorePage(page: PageSummary) {
        if (!confirm(`Kembalikan halaman "${page.title}" dari trash?`)) {
            return;
        }

        router.put(
            pagesRoutes.restore.url(page.id),
            {},
            {
                preserveScroll: true,
            },
        );
    }

    function forceDeletePage(page: PageSummary) {
        if (
            !confirm(
                `Hapus permanen halaman "${page.title}"? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            return;
        }

        router.delete(pagesRoutes.forceDelete.url(page.id), {
            preserveScroll: true,
        });
    }

    const columns: DataTableColumn<PageSummary>[] = [
        {
            key: 'title',
            header: 'Judul',
            render: (row) => row.title,
        },
        {
            key: 'mode',
            header: 'Mode',
            render: (row) => (
                <Badge variant={row.mode === 'Code' ? 'default' : 'secondary'}>
                    {row.mode}
                </Badge>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <Badge
                    variant={
                        row.status === 'Published' ? 'default' : 'secondary'
                    }
                >
                    {row.status ?? '-'}
                </Badge>
            ),
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) =>
                canManageTrash ? (
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => restorePage(row)}
                        >
                            Restore
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            onClick={() => forceDeletePage(row)}
                        >
                            Hapus permanen
                        </Button>
                    </div>
                ) : (
                    <span className="text-sm text-muted-foreground">
                        Hanya Admin/Editor
                    </span>
                ),
        },
    ];

    return (
        <>
            <Head title="Trash Halaman" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Trash Halaman</h1>
                    <Button asChild variant="outline">
                        <a href={pagesIndex.url()}>Kembali ke Halaman</a>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={pages.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Trash kosong."
                />
            </div>
        </>
    );
}

PagesTrash.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Halaman',
            href: pagesIndex(),
        },
        {
            title: 'Trash',
            href: pagesRoutes.trash(),
        },
    ],
};
