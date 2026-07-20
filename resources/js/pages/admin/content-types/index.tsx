import { Head, router } from '@inertiajs/react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import type { LanguageOption } from '@/components/admin/language-tabs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';
import contentTypesRoutes, {
    index as contentTypesIndex,
} from '@/routes/admin/content-types';

type ContentTypeTranslation = {
    language_id: number;
    name: string;
    description: string | null;
};

type ContentType = {
    id: number;
    slug: string;
    icon: string | null;
    writing_style_id: number | null;
    archive_template_key: string;
    single_template_key: string;
    is_active: boolean;
    sort_order: number;
    translations: ContentTypeTranslation[];
};

/** Ambil nama jenis konten untuk bahasa utama (fallback ke translation pertama). */
function contentTypeName(
    contentType: ContentType,
    languages: LanguageOption[],
): string {
    const primaryId = languages[0]?.id;
    const match =
        contentType.translations.find((t) => t.language_id === primaryId) ??
        contentType.translations[0];

    return match?.name ?? '(tanpa nama)';
}

export default function ContentTypesIndex({
    contentTypes,
    languages,
}: {
    contentTypes: ContentType[];
    languages: LanguageOption[];
}) {
    function deleteContentType(contentType: ContentType) {
        if (
            !confirm(
                `Hapus jenis konten "${contentTypeName(contentType, languages)}"?`,
            )
        ) {
            return;
        }

        router.delete(contentTypesRoutes.destroy.url(contentType.id), {
            preserveScroll: true,
        });
    }

    const columns: DataTableColumn<ContentType>[] = [
        {
            key: 'name',
            header: 'Nama',
            render: (row) => contentTypeName(row, languages),
        },
        {
            key: 'slug',
            header: 'Slug',
            render: (row) => <code className="text-xs">{row.slug}</code>,
        },
        {
            key: 'is_active',
            header: 'Status',
            render: (row) => (
                <Badge variant={row.is_active ? 'default' : 'secondary'}>
                    {row.is_active ? 'Aktif' : 'Nonaktif'}
                </Badge>
            ),
        },
        {
            key: 'sort_order',
            header: 'Urutan',
            render: (row) => row.sort_order,
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button size="sm" variant="outline" asChild>
                        <a href={contentTypesRoutes.edit.url(row.id)}>Ubah</a>
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={() => deleteContentType(row)}
                    >
                        Hapus
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Jenis Konten" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Jenis Konten</h1>
                    <Button asChild>
                        <a href={contentTypesRoutes.create.url()}>
                            Tambah jenis konten
                        </a>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={contentTypes}
                    rowKey={(row) => row.id}
                    emptyMessage="Belum ada jenis konten. Tambahkan jenis konten pertama."
                />
            </div>
        </>
    );
}

ContentTypesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Jenis Konten',
            href: contentTypesIndex(),
        },
    ],
};
