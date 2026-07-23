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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes/admin';
import categoriesRoutes, {
    index as categoriesIndex,
} from '@/routes/admin/categories';

type CategoryTranslation = {
    language_id: number;
    name: string;
};

type Category = {
    id: number;
    slug: string;
    parent_id: number | null;
    sort_order: number;
    depth: number;
    translations: CategoryTranslation[];
};

type CategoryFormData = {
    slug: string;
    parent_id: number | null;
    sort_order: number;
    translations: Record<number, string>;
};

/** Ambil nama kategori untuk bahasa utama (fallback ke translation pertama). */
function categoryName(category: Category, languages: LanguageOption[]): string {
    const primaryId = languages[0]?.id;
    const match =
        category.translations.find((t) => t.language_id === primaryId) ??
        category.translations[0];

    return match?.name ?? '(tanpa nama)';
}

function buildInitialTranslations(
    languages: LanguageOption[],
    category?: Category,
): Record<number, string> {
    const values: Record<number, string> = {};

    for (const lang of languages) {
        const existing = category?.translations.find(
            (t) => t.language_id === lang.id,
        );
        values[lang.id] = existing?.name ?? '';
    }

    return values;
}

function isDescendant(
    candidate: Category,
    ancestorId: number,
    categories: Category[],
): boolean {
    const visited = new Set<number>();
    let parentId = candidate.parent_id;

    while (parentId !== null && !visited.has(parentId)) {
        if (parentId === ancestorId) {
            return true;
        }

        visited.add(parentId);
        parentId =
            categories.find((item) => item.id === parentId)?.parent_id ?? null;
    }

    return false;
}

function CategoryFormDialog({
    category,
    languages,
    categories,
    onClose,
}: {
    category?: Category;
    languages: LanguageOption[];
    categories: Category[];
    onClose: () => void;
}) {
    const isEditing = category !== undefined;

    const form = useForm<CategoryFormData>({
        slug: category?.slug ?? '',
        parent_id: category?.parent_id ?? null,
        sort_order: category?.sort_order ?? 0,
        translations: buildInitialTranslations(languages, category),
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
            form.put(categoriesRoutes.update.url(category.id), options);
        } else {
            form.post(categoriesRoutes.store.url(), options);
        }
    }

    const parentOptions = categories.filter(
        (candidate) =>
            candidate.id !== category?.id &&
            (category === undefined ||
                !isDescendant(candidate, category.id, categories)),
    );

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Ubah kategori' : 'Tambah kategori'}
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
                        idPrefix="category"
                    />

                    <div className="space-y-1">
                        <Label htmlFor="category-slug">
                            Slug (opsional — otomatis dari nama)
                        </Label>
                        <Input
                            id="category-slug"
                            value={form.data.slug}
                            onChange={(e) =>
                                form.setData('slug', e.target.value)
                            }
                            placeholder="slug-kategori"
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="category-parent">Induk kategori</Label>
                        <Select
                            value={
                                form.data.parent_id !== null
                                    ? String(form.data.parent_id)
                                    : 'none'
                            }
                            onValueChange={(value) =>
                                form.setData(
                                    'parent_id',
                                    value === 'none' ? null : Number(value),
                                )
                            }
                        >
                            <SelectTrigger
                                id="category-parent"
                                className="w-full"
                            >
                                <SelectValue placeholder="Tanpa induk" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    Tanpa induk
                                </SelectItem>
                                {parentOptions.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {`${'— '.repeat(c.depth)}${categoryName(c, languages)}`}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.parent_id} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="category-sort-order">Urutan</Label>
                        <Input
                            id="category-sort-order"
                            type="number"
                            value={form.data.sort_order}
                            onChange={(e) =>
                                form.setData(
                                    'sort_order',
                                    Number.parseInt(e.target.value, 10) || 0,
                                )
                            }
                            className="w-32"
                        />
                        <InputError message={form.errors.sort_order} />
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

export default function CategoriesIndex({
    categories,
    languages,
}: {
    categories: Category[];
    languages: LanguageOption[];
}) {
    const [dialogFor, setDialogFor] = useState<number | 'new' | null>(null);

    function deleteCategory(category: Category) {
        if (
            !confirm(`Hapus kategori "${categoryName(category, languages)}"?`)
        ) {
            return;
        }

        router.delete(categoriesRoutes.destroy.url(category.id), {
            preserveScroll: true,
        });
    }

    const columns: DataTableColumn<Category>[] = [
        {
            key: 'name',
            header: 'Nama',
            render: (category) => (
                <span
                    className="block"
                    style={{
                        paddingLeft: `${category.depth * 1.25}rem`,
                    }}
                >
                    {category.depth > 0 && (
                        <span
                            className="mr-2 text-muted-foreground"
                            aria-hidden="true"
                        >
                            ↳
                        </span>
                    )}
                    {categoryName(category, languages)}
                </span>
            ),
        },
        {
            key: 'slug',
            header: 'Slug',
            render: (row) => <code className="text-xs">{row.slug}</code>,
        },
        {
            key: 'parent',
            header: 'Induk',
            render: (row) => {
                const parent = categories.find((c) => c.id === row.parent_id);

                return parent ? categoryName(parent, languages) : '—';
            },
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
                        onClick={() => deleteCategory(row)}
                    >
                        Hapus
                    </Button>
                </div>
            ),
        },
    ];

    const editingCategory =
        typeof dialogFor === 'number'
            ? categories.find((c) => c.id === dialogFor)
            : undefined;

    return (
        <>
            <Head title="Kategori" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Kategori</h1>
                    <Button type="button" onClick={() => setDialogFor('new')}>
                        Tambah kategori
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={categories}
                    rowKey={(row) => row.id}
                    emptyMessage="Belum ada kategori. Tambahkan kategori pertama."
                />
            </div>

            {dialogFor !== null && (
                <CategoryFormDialog
                    key={dialogFor}
                    category={editingCategory}
                    languages={languages}
                    categories={categories}
                    onClose={() => setDialogFor(null)}
                />
            )}
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Kategori',
            href: categoriesIndex(),
        },
    ],
};
