import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import LanguageTabs from '@/components/admin/language-tabs';
import type { LanguageOption } from '@/components/admin/language-tabs';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes/admin';
import tagsRoutes, { index as tagsIndex } from '@/routes/admin/tags';

type TagTranslation = {
    language_id: number;
    name: string;
};

type Tag = {
    id: number;
    slug: string;
    translations: TagTranslation[];
};

type TagFormData = {
    slug: string;
    translations: Record<number, string>;
};

/** Ambil nama tag untuk bahasa utama (fallback ke translation pertama). */
function tagName(tag: Tag, languages: LanguageOption[]): string {
    const primaryId = languages[0]?.id;
    const match =
        tag.translations.find((t) => t.language_id === primaryId) ??
        tag.translations[0];

    return match?.name ?? '(tanpa nama)';
}

function buildInitialTranslations(
    languages: LanguageOption[],
    tag?: Tag,
): Record<number, string> {
    const values: Record<number, string> = {};

    for (const lang of languages) {
        const existing = tag?.translations.find(
            (t) => t.language_id === lang.id,
        );
        values[lang.id] = existing?.name ?? '';
    }

    return values;
}

function TagFormDialog({
    tag,
    languages,
    onClose,
}: {
    tag?: Tag;
    languages: LanguageOption[];
    onClose: () => void;
}) {
    const isEditing = tag !== undefined;

    const form = useForm<TagFormData>({
        slug: tag?.slug ?? '',
        translations: buildInitialTranslations(languages, tag),
    });

    // Peta index array (posisi di `languages`) → error, karena backend mengirim
    // error dengan key `translations.{index}.name`, bukan berdasarkan language_id.
    const translationErrors: Record<number, string | undefined> = {};
    languages.forEach((lang, i) => {
        translationErrors[lang.id] = (
            form.errors as Record<string, string | undefined>
        )[`translations.${i}.name`];
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();

        form.transform((data) => ({
            ...data,
            translations: languages.map((lang) => ({
                language_id: lang.id,
                name: data.translations[lang.id] ?? '',
            })),
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEditing) {
            form.put(tagsRoutes.update.url(tag.id), options);
        } else {
            form.post(tagsRoutes.store.url(), options);
        }
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Ubah tag' : 'Tambah tag'}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <LanguageTabs
                        languages={languages}
                        values={form.data.translations}
                        errors={translationErrors}
                        onChange={(languageId, value) =>
                            form.setData('translations', {
                                ...form.data.translations,
                                [languageId]: value,
                            })
                        }
                        idPrefix="tag"
                    />

                    <div className="space-y-1">
                        <Label htmlFor="tag-slug">
                            Slug (opsional — otomatis dari nama)
                        </Label>
                        <Input
                            id="tag-slug"
                            value={form.data.slug}
                            onChange={(e) =>
                                form.setData('slug', e.target.value)
                            }
                            placeholder="slug-tag"
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function TagsIndex({
    tags,
    languages,
}: {
    tags: Tag[];
    languages: LanguageOption[];
}) {
    const [dialogFor, setDialogFor] = useState<number | 'new' | null>(null);

    function deleteTag(tag: Tag) {
        if (!confirm(`Hapus tag "${tagName(tag, languages)}"?`)) {
            return;
        }

        router.delete(tagsRoutes.destroy.url(tag.id), {
            preserveScroll: true,
        });
    }

    const columns: DataTableColumn<Tag>[] = [
        {
            key: 'name',
            header: 'Nama',
            render: (row) => tagName(row, languages),
        },
        {
            key: 'slug',
            header: 'Slug',
            render: (row) => <code className="text-xs">{row.slug}</code>,
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => setDialogFor(row.id)}
                    >
                        Ubah
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={() => deleteTag(row)}
                    >
                        Hapus
                    </Button>
                </div>
            ),
        },
    ];

    const editingTag =
        typeof dialogFor === 'number'
            ? tags.find((t) => t.id === dialogFor)
            : undefined;

    return (
        <>
            <Head title="Tag" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Tag</h1>
                    <Button type="button" onClick={() => setDialogFor('new')}>
                        Tambah tag
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={tags}
                    rowKey={(row) => row.id}
                    emptyMessage="Belum ada tag. Tambahkan tag pertama."
                />
            </div>

            {dialogFor !== null && (
                <TagFormDialog
                    key={dialogFor}
                    tag={editingTag}
                    languages={languages}
                    onClose={() => setDialogFor(null)}
                />
            )}
        </>
    );
}

TagsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Tag',
            href: tagsIndex(),
        },
    ],
};
