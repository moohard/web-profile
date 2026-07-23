import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import { PermanentDeleteDialog } from '@/components/admin/permanent-delete-dialog';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';
import postsRoutes, {
    index as postsIndex,
    trash as postsTrash,
} from '@/routes/admin/posts';

type TrashPost = {
    id: number;
    title: string;
    typeName: string;
    author: string;
    deleted_at: string;
    canRestore: boolean;
    canForceDelete: boolean;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function PostsTrash({ posts }: { posts: Paginated<TrashPost> }) {
    const [processingId, setProcessingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<TrashPost | null>(null);

    function restorePost(post: TrashPost) {
        router.patch(
            postsRoutes.restore.url(post.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessingId(post.id),
                onFinish: () => setProcessingId(null),
            },
        );
    }

    function permanentlyDeletePost() {
        if (!deleteTarget) {
            return;
        }

        router.delete(postsRoutes.forceDelete.url(deleteTarget.id), {
            preserveScroll: true,
            onStart: () => setProcessingId(deleteTarget.id),
            onSuccess: () => setDeleteTarget(null),
            onFinish: () => setProcessingId(null),
        });
    }

    const columns: DataTableColumn<TrashPost>[] = [
        { key: 'title', header: 'Judul', render: (row) => row.title },
        { key: 'type', header: 'Jenis', render: (row) => row.typeName },
        { key: 'author', header: 'Penulis', render: (row) => row.author },
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
                            onClick={() => restorePost(row)}
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
            <Head title="Trash Posts" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Trash Posts</h1>
                        <p className="text-sm text-muted-foreground">
                            Pulihkan post atau hapus data secara permanen.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={postsIndex()}>Kembali ke Posts</Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={posts.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Trash Post kosong."
                />

                {posts.links.length > 3 && (
                    <nav
                        className="flex flex-wrap gap-2"
                        aria-label="Pagination Trash Post"
                    >
                        {posts.links.map((link, index) =>
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
                onConfirm={permanentlyDeletePost}
            />
        </>
    );
}

PostsTrash.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Posts', href: postsIndex() },
        { title: 'Trash', href: postsTrash() },
    ],
};
