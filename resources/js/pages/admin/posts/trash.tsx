import { Head, router } from '@inertiajs/react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import PostStatusIndicators from '@/components/admin/post-status-indicators';
import type { PostLanguageStatus } from '@/components/admin/post-status-indicators';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';
import postsRoutes, { index as postsIndex } from '@/routes/admin/posts';

type PostSummary = {
    id: number;
    title: string;
    typeName: string;
    typeSlug: string;
    /** Status per-bahasa aktif (mis. ID Published, EN Draft) — lihat PostController::toSummary. */
    statuses: PostLanguageStatus[];
    author: string;
    updated_at: string;
    editUrl: string;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: { current_page: number; last_page: number };
};

export default function PostsTrash({
    posts,
    canManageTrash,
}: {
    posts: Paginated<PostSummary>;
    canManageTrash: boolean;
}) {
    function restorePost(post: PostSummary) {
        if (!confirm(`Kembalikan post "${post.title}" dari trash?`)) {
            return;
        }

        router.put(
            postsRoutes.restore.url(post.id),
            {},
            {
                preserveScroll: true,
            },
        );
    }

    function forceDeletePost(post: PostSummary) {
        if (
            !confirm(
                `Hapus permanen post "${post.title}"? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            return;
        }

        router.delete(postsRoutes.forceDelete.url(post.id), {
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
            render: (row) => <PostStatusIndicators statuses={row.statuses} />,
        },
        {
            key: 'author',
            header: 'Penulis',
            render: (row) => row.author,
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
                            onClick={() => restorePost(row)}
                        >
                            Restore
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            onClick={() => forceDeletePost(row)}
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
            <Head title="Trash Posts" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Trash Posts</h1>
                    <Button asChild variant="outline">
                        <a href={postsIndex.url()}>Kembali ke Posts</a>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={posts.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Trash kosong."
                />
            </div>
        </>
    );
}

PostsTrash.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Posts',
            href: postsIndex(),
        },
        {
            title: 'Trash',
            href: postsRoutes.trash(),
        },
    ],
};
