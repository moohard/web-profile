import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import { PermanentDeleteDialog } from '@/components/admin/permanent-delete-dialog';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';
import pagesRoutes, {
    index as pagesIndex,
    trash as pagesTrash,
} from '@/routes/admin/pages';

type TrashPage = {
    id: number;
    title: string;
    mode: string;
    deleted_at: string;
    canRestore: boolean;
    canForceDelete: boolean;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function PagesTrash({ pages }: { pages: Paginated<TrashPage> }) {
    const [processingId, setProcessingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<TrashPage | null>(null);

    function restorePage(page: TrashPage) {
        router.patch(
            pagesRoutes.restore.url(page.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessingId(page.id),
                onFinish: () => setProcessingId(null),
            },
        );
    }

    function permanentlyDeletePage() {
        if (!deleteTarget) {
            return;
        }

        router.delete(pagesRoutes.forceDelete.url(deleteTarget.id), {
            preserveScroll: true,
            onStart: () => setProcessingId(deleteTarget.id),
            onSuccess: () => setDeleteTarget(null),
            onFinish: () => setProcessingId(null),
        });
    }

    const columns: DataTableColumn<TrashPage>[] = [
        { key: 'title', header: 'Judul', render: (row) => row.title },
        { key: 'mode', header: 'Mode', render: (row) => row.mode },
        {
            key: 'deleted_at',
            header: 'Dihapus',
            render: (row) =>
                row.deleted_at
                    ? new Date(row.deleted_at).toLocaleString('id-ID')
                    : '-',
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    {row.canRestore && (
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={processingId === row.id}
                            onClick={() => restorePage(row)}
                        >
                            Pulihkan
                        </Button>
                    )}
                    {row.canForceDelete && (
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            disabled={processingId === row.id}
                            onClick={() => setDeleteTarget(row)}
                        >
                            Hapus permanen
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Trash Halaman" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Trash Halaman
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Pulihkan halaman atau hapus data secara permanen.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={pagesIndex()}>Kembali ke Halaman</Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={pages.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Trash Halaman kosong."
                />

                {pages.links.length > 3 && (
                    <nav
                        className="flex flex-wrap gap-2"
                        aria-label="Pagination Trash Halaman"
                    >
                        {pages.links.map((link, index) =>
                            link.url ? (
                                <Button
                                    key={`${link.label}-${index}`}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    asChild
                                >
                                    <Link href={link.url} preserveScroll>
                                        {link.label
                                            .replace('&laquo;', '«')
                                            .replace('&raquo;', '»')}
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    key={`${link.label}-${index}`}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                >
                                    {link.label
                                        .replace('&laquo;', '«')
                                        .replace('&raquo;', '»')}
                                </Button>
                            ),
                        )}
                    </nav>
                )}
            </div>

            <PermanentDeleteDialog
                open={deleteTarget !== null}
                itemTitle={deleteTarget?.title ?? ''}
                processing={
                    deleteTarget !== null && processingId === deleteTarget.id
                }
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                onConfirm={permanentlyDeletePage}
            />
        </>
    );
}

PagesTrash.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Halaman', href: pagesIndex() },
        { title: 'Trash', href: pagesTrash() },
    ],
};
