import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
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
import postsRoutes, { index as postsIndex } from '@/routes/admin/posts';

type ContentTypeOption = {
    id: number;
    slug: string;
    name: string;
};

type PostSummary = {
    id: number;
    title: string;
    typeName: string;
    typeSlug: string;
    status: string | null;
    statuses: {
        code: string;
        name: string;
        status: string | null;
    }[];
    author: string;
    updated_at: string;
    editUrl: string;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

type Filters = {
    type: string | null;
    status: string | null;
};

export default function PostsIndex({
    posts,
    contentTypes,
    filters,
}: {
    posts: Paginated<PostSummary>;
    contentTypes: ContentTypeOption[];
    filters: Filters;
}) {
    const [loading, setLoading] = useState(false);

    function applyFilters(next: Partial<Filters>) {
        const query: Record<string, string> = {};
        const type = next.type !== undefined ? next.type : filters.type;
        const status = next.status !== undefined ? next.status : filters.status;

        if (type) {
            query.type = type;
        }

        if (status) {
            query.status = status;
        }

        router.get(postsIndex.url({ query }), undefined, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    }

    function deletePost(post: PostSummary) {
        if (!confirm(`Hapus post "${post.title}"?`)) {
            return;
        }

        router.delete(postsRoutes.destroy.url(post.id), {
            preserveScroll: true,
        });
    }

    const columns: DataTableColumn<PostSummary>[] = [
        {
            key: 'title',
            header: 'Judul',
            render: (row) => row.title,
        },
        {
            key: 'type',
            header: 'Jenis',
            render: (row) => row.typeName,
        },
        {
            key: 'status',
            header: 'Status bahasa',
            render: (row) => (
                <div className="flex min-w-40 flex-wrap gap-1.5">
                    {row.statuses.map((status) => (
                        <Badge
                            key={status.code}
                            variant={
                                status.status === 'Published'
                                    ? 'default'
                                    : 'secondary'
                            }
                            title={status.name}
                        >
                            {status.code.toUpperCase()}: {status.status ?? '—'}
                        </Badge>
                    ))}
                </div>
            ),
        },
        {
            key: 'author',
            header: 'Penulis',
            render: (row) => row.author,
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
                        <Link href={postsRoutes.edit.url(row.id)}>Ubah</Link>
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={() => deletePost(row)}
                    >
                        Hapus
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Posts" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Posts</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={postsRoutes.trash.url()}>Trash</Link>
                        </Button>
                        <Button asChild>
                            <Link href={postsRoutes.create.url()}>
                                Tambah post
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Select
                        value={filters.type ?? 'all'}
                        onValueChange={(value) =>
                            applyFilters({
                                type: value === 'all' ? null : value,
                            })
                        }
                    >
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="Semua jenis konten" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                Semua jenis konten
                            </SelectItem>
                            {contentTypes.map((ct) => (
                                <SelectItem key={ct.slug} value={ct.slug}>
                                    {ct.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

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

                <div
                    aria-busy={loading}
                    className={loading ? 'opacity-60' : undefined}
                >
                    {loading && (
                        <p className="mb-3 text-sm text-muted-foreground">
                            Memuat posts…
                        </p>
                    )}
                    <DataTable
                        columns={columns}
                        data={posts.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Belum ada post. Tambahkan post pertama."
                    />
                </div>

                {posts.last_page > 1 && (
                    <nav
                        aria-label="Pagination posts"
                        className="flex flex-wrap justify-center gap-2"
                    >
                        {posts.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={`${link.label}-${index}`}
                                    href={link.url}
                                    preserveState
                                    preserveScroll
                                    onStart={() => setLoading(true)}
                                    onFinish={() => setLoading(false)}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                    className={`rounded-md border px-3 py-2 text-sm ${
                                        link.active
                                            ? 'bg-primary text-primary-foreground'
                                            : 'hover:bg-muted'
                                    }`}
                                >
                                    {link.label
                                        .replace('&laquo;', '‹')
                                        .replace('&raquo;', '›')}
                                </Link>
                            ) : (
                                <span
                                    key={`${link.label}-${index}`}
                                    className="rounded-md border px-3 py-2 text-sm text-muted-foreground opacity-50"
                                >
                                    {link.label
                                        .replace('&laquo;', '‹')
                                        .replace('&raquo;', '›')}
                                </span>
                            ),
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

PostsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Posts',
            href: postsIndex(),
        },
    ],
};
