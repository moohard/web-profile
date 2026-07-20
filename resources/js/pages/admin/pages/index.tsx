import { Head, router } from '@inertiajs/react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

type Filters = {
    status: string | null;
};

export default function PagesIndex({
    pages,
    filters,
}: {
    pages: Paginated<PageSummary>;
    filters: Filters;
}) {
    function applyFilters(next: Partial<Filters>) {
        const query: Record<string, string> = {};
        const status = next.status !== undefined ? next.status : filters.status;

        if (status) {
            query.status = status;
        }

        router.get(pagesIndex.url({ query }), undefined, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function deletePage(page: PageSummary) {
        if (!confirm(`Hapus halaman "${page.title}"?`)) {
            return;
        }

        router.delete(pagesRoutes.destroy.url(page.id), {
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
            key: 'updated_at',
            header: 'Diperbarui',
            render: (row) =>
                row.updated_at
                    ? new Date(row.updated_at).toLocaleString('id-ID')
                    : '-',
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button size="sm" variant="outline" asChild>
                        <a href={pagesRoutes.edit.url(row.id)}>Ubah</a>
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={() => deletePage(row)}
                    >
                        Hapus
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Halaman" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Halaman</h1>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <a href={pagesRoutes.trash.url()}>Trash</a>
                        </Button>
                        <Button asChild>
                            <a href={pagesRoutes.create.url()}>
                                Tambah halaman
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={(value) =>
                            applyFilters({
                                status: value === 'all' ? null : value,
                            })
                        }
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="Semua status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua status</SelectItem>
                            <SelectItem value="Draft">Draft</SelectItem>
                            <SelectItem value="Published">Published</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <DataTable
                    columns={columns}
                    data={pages.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Belum ada halaman. Tambahkan halaman pertama."
                />
            </div>
        </>
    );
}

PagesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Halaman',
            href: pagesIndex(),
        },
    ],
};
