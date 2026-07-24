import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import LanguageTabs from '@/components/admin/language-tabs';
import type { LanguageOption } from '@/components/admin/language-tabs';
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
import ratingCriteriaRoutes from '@/routes/admin/rating-criteria';

type Criterion = {
    id: number;
    is_active: boolean;
    sort_order: number;
    translations: { language_id: number; name: string }[];
};
type CriterionForm = {
    is_active: boolean;
    sort_order: number;
    translations: Record<number, string>;
};

function RatingCriterionDialog({
    criterion,
    languages,
    onClose,
}: {
    criterion?: Criterion;
    languages: LanguageOption[];
    onClose: () => void;
}) {
    const form = useForm<CriterionForm>({
        is_active: criterion?.is_active ?? true,
        sort_order: criterion?.sort_order ?? 0,
        translations: Object.fromEntries(
            languages.map((language) => [
                language.id,
                criterion?.translations.find(
                    (translation) => translation.language_id === language.id,
                )?.name ?? '',
            ]),
        ),
    });
    const isEditing = criterion !== undefined;

    function submit() {
        form.transform((data) => ({
            ...data,
            translations: languages.map((language) => ({
                language_id: language.id,
                name: data.translations[language.id] ?? '',
            })),
        }));
        const options = { preserveScroll: true, onSuccess: onClose };

        if (isEditing) {
            form.put(ratingCriteriaRoutes.update.url(criterion.id), options);
        } else {
            form.post(ratingCriteriaRoutes.store.url(), options);
        }
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Ubah kriteria' : 'Tambah kriteria'}
                    </DialogTitle>
                </DialogHeader>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit();
                    }}
                    className="space-y-4"
                >
                    <LanguageTabs
                        languages={languages}
                        values={form.data.translations}
                        errors={{}}
                        onChange={(languageId, value) =>
                            form.setData('translations', {
                                ...form.data.translations,
                                [languageId]: value,
                            })
                        }
                        idPrefix="rating-criterion"
                    />
                    <div className="flex items-end gap-4">
                        <div className="space-y-1">
                            <Label htmlFor="rating-sort-order">Urutan</Label>
                            <Input
                                id="rating-sort-order"
                                type="number"
                                value={form.data.sort_order}
                                onChange={(event) =>
                                    form.setData(
                                        'sort_order',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) =>
                                    form.setData(
                                        'is_active',
                                        event.target.checked,
                                    )
                                }
                            />{' '}
                            Aktif
                        </label>
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

export default function RatingCriteriaIndex({
    criteria,
    languages,
}: {
    criteria: Criterion[];
    languages: LanguageOption[];
}) {
    const [editing, setEditing] = useState<Criterion | null>(null);
    const [creating, setCreating] = useState(false);
    const columns: DataTableColumn<Criterion>[] = [
        {
            key: 'name',
            header: 'Kriteria',
            render: (criterion) =>
                criterion.translations.find(
                    (translation) =>
                        translation.language_id === languages[0]?.id,
                )?.name ??
                criterion.translations[0]?.name ??
                '-',
        },
        {
            key: 'order',
            header: 'Urutan',
            render: (criterion) => criterion.sort_order,
        },
        {
            key: 'status',
            header: 'Status',
            render: (criterion) =>
                criterion.is_active ? 'Aktif' : 'Tidak aktif',
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (criterion) => (
                <div className="flex justify-end gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => setEditing(criterion)}
                    >
                        Ubah
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        onClick={() => {
                            if (confirm('Hapus kriteria ini?')) {
                                router.delete(
                                    ratingCriteriaRoutes.destroy.url(
                                        criterion.id,
                                    ),
                                    { preserveScroll: true },
                                );
                            }
                        }}
                    >
                        Hapus
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Kriteria Penilaian" />
            <div className="space-y-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Kriteria Penilaian
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola kriteria untuk penilaian layanan publik.
                        </p>
                    </div>
                    <Button onClick={() => setCreating(true)}>
                        Tambah kriteria
                    </Button>
                </div>
                <DataTable
                    columns={columns}
                    data={criteria}
                    rowKey={(criterion) => criterion.id}
                    emptyMessage="Belum ada kriteria."
                />
            </div>
            {creating && (
                <RatingCriterionDialog
                    languages={languages}
                    onClose={() => setCreating(false)}
                />
            )}
            {editing && (
                <RatingCriterionDialog
                    criterion={editing}
                    languages={languages}
                    onClose={() => setEditing(null)}
                />
            )}
        </>
    );
}
