import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import languagesRoutes, {
    index as languagesIndex,
} from '@/routes/admin/settings/languages';

type Language = {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    is_default: boolean;
    sort_order: number;
    is_in_use: boolean;
};

type LanguageFormData = {
    code: string;
    name: string;
    is_active: boolean;
    is_default: boolean;
    sort_order: number;
};

function LanguageFormDialog({
    language,
    onClose,
}: {
    language?: Language;
    onClose: () => void;
}) {
    const isEditing = language !== undefined;
    const form = useForm<LanguageFormData>({
        code: language?.code ?? '',
        name: language?.name ?? '',
        is_active: language?.is_active ?? true,
        is_default: language?.is_default ?? false,
        sort_order: language?.sort_order ?? 0,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (language) {
            form.put(languagesRoutes.update.url(language.id), options);
        } else {
            form.post(languagesRoutes.store.url(), options);
        }
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Ubah bahasa' : 'Tambah bahasa'}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1">
                        <Label htmlFor="language-code">Kode bahasa</Label>
                        <Input
                            id="language-code"
                            value={form.data.code}
                            maxLength={2}
                            disabled={language?.is_in_use}
                            onChange={(event) =>
                                form.setData(
                                    'code',
                                    event.target.value.toLowerCase(),
                                )
                            }
                            placeholder="fr"
                        />
                        <p className="text-xs text-muted-foreground">
                            Tepat dua huruf lowercase. Kode terkunci setelah
                            bahasa dipakai.
                        </p>
                        <InputError message={form.errors.code} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="language-name">Nama</Label>
                        <Input
                            id="language-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="language-order">Urutan</Label>
                        <Input
                            id="language-order"
                            type="number"
                            min={0}
                            value={form.data.sort_order}
                            onChange={(event) =>
                                form.setData(
                                    'sort_order',
                                    Number(event.target.value),
                                )
                            }
                        />
                        <InputError message={form.errors.sort_order} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.is_active}
                            disabled={language?.is_default}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                        />
                        Aktif
                    </label>
                    <InputError message={form.errors.is_active} />

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.is_default}
                            disabled={language?.is_default}
                            onCheckedChange={(checked) =>
                                form.setData('is_default', checked === true)
                            }
                        />
                        Jadikan bahasa default
                    </label>
                    <InputError message={form.errors.is_default} />

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

export default function LanguagesIndex({
    languages,
}: {
    languages: Language[];
}) {
    const [dialogFor, setDialogFor] = useState<number | 'new' | null>(null);

    function deleteLanguage(language: Language) {
        if (
            !confirm(
                `Hapus bahasa "${language.name}"? Bahasa yang sudah dipakai atau menjadi default tidak dapat dihapus.`,
            )
        ) {
            return;
        }

        router.delete(languagesRoutes.destroy.url(language.id), {
            preserveScroll: true,
        });
    }

    const columns: DataTableColumn<Language>[] = [
        {
            key: 'name',
            header: 'Bahasa',
            render: (language) => (
                <div>
                    <div className="font-medium">{language.name}</div>
                    <code className="text-xs text-muted-foreground">
                        {language.code}
                    </code>
                </div>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (language) => (
                <div className="flex gap-2">
                    <Badge variant={language.is_active ? 'default' : 'outline'}>
                        {language.is_active ? 'Aktif' : 'Inactive'}
                    </Badge>
                    {language.is_default && (
                        <Badge variant="secondary">Default</Badge>
                    )}
                </div>
            ),
        },
        {
            key: 'sort_order',
            header: 'Urutan',
            render: (language) => language.sort_order,
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (language) => (
                <div className="flex justify-end gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => setDialogFor(language.id)}
                    >
                        Ubah
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        disabled={language.is_default || language.is_in_use}
                        onClick={() => deleteLanguage(language)}
                    >
                        Hapus
                    </Button>
                </div>
            ),
        },
    ];

    const editingLanguage =
        typeof dialogFor === 'number'
            ? languages.find((language) => language.id === dialogFor)
            : undefined;

    return (
        <>
            <Head title="Bahasa" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Bahasa</h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola bahasa aktif dan URL locale situs publik.
                        </p>
                    </div>
                    <Button type="button" onClick={() => setDialogFor('new')}>
                        Tambah bahasa
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={languages}
                    rowKey={(language) => language.id}
                    emptyMessage="Belum ada bahasa."
                />
            </div>

            {dialogFor !== null && (
                <LanguageFormDialog
                    key={dialogFor}
                    language={editingLanguage}
                    onClose={() => setDialogFor(null)}
                />
            )}
        </>
    );
}

LanguagesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Bahasa',
            href: languagesIndex(),
        },
    ],
};
