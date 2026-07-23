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
    author: string;
    updated_at: string;
    editUrl: string;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: { current_page: number; last_page: number };
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
                        <a href={postsRoutes.edit.url(row.id)}>Ubah</a>
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
                            <a href={postsRoutes.trash.url()}>Trash</a>
                        </Button>
                        <Button asChild>
                            <a href={postsRoutes.create.url()}>Tambah post</a>
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

                <DataTable
                    columns={columns}
                    data={posts.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Belum ada post. Tambahkan post pertama."
                />
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
