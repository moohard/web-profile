import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import writingStylesRoutes from '@/routes/admin/writing-styles';

type WritingStyleItem = {
    id: number;
    name: string;
    prompt: string | null;
    is_in_use: boolean;
};

type WritingStyleForm = {
    name: string;
    prompt: string;
};

export default function WritingStylesIndex({
    writingStyles,
}: {
    writingStyles: WritingStyleItem[];
}) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const form = useForm<WritingStyleForm>({
        name: '',
        prompt: '',
    });

    function resetForm() {
        setEditingId(null);
        form.setData({ name: '', prompt: '' });
        form.clearErrors();
    }

    function edit(style: WritingStyleItem) {
        setEditingId(style.id);
        form.setData({
            name: style.name,
            prompt: style.prompt ?? '',
        });
        form.clearErrors();
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: resetForm,
        };

        if (editingId !== null) {
            form.put(writingStylesRoutes.update.url(editingId), options);

            return;
        }

        form.post(writingStylesRoutes.store.url(), options);
    }

    function destroy(style: WritingStyleItem) {
        if (
            style.is_in_use ||
            !window.confirm(
                `Hapus gaya bahasa "${style.name}"? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            return;
        }

        router.delete(writingStylesRoutes.destroy.url(style.id), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Gaya Bahasa" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Gaya Bahasa</h1>
                    <p className="text-sm text-muted-foreground">
                        Atur instruksi gaya yang dipakai saat penyempurnaan
                        konten dengan AI.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {editingId === null
                                ? 'Tambah gaya bahasa'
                                : 'Ubah gaya bahasa'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-1">
                                <Label htmlFor="writing-style-name">Nama</Label>
                                <Input
                                    id="writing-style-name"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    maxLength={255}
                                />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="writing-style-prompt">
                                    Instruksi prompt
                                </Label>
                                <textarea
                                    id="writing-style-prompt"
                                    value={form.data.prompt}
                                    onChange={(event) =>
                                        form.setData(
                                            'prompt',
                                            event.target.value,
                                        )
                                    }
                                    rows={6}
                                    maxLength={10000}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Contoh: Gunakan kalimat aktif, formal, dan ringkas."
                                />
                                <InputError message={form.errors.prompt} />
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {editingId === null
                                        ? 'Simpan'
                                        : 'Simpan perubahan'}
                                </Button>
                                {editingId !== null && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={resetForm}
                                    >
                                        Batal
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div className="space-y-3">
                    {writingStyles.length === 0 ? (
                        <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                            Belum ada gaya bahasa.
                        </p>
                    ) : (
                        writingStyles.map((style) => (
                            <Card key={style.id}>
                                <CardContent className="flex items-start justify-between gap-4 py-4">
                                    <div className="min-w-0 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="font-medium">
                                                {style.name}
                                            </h2>
                                            {style.is_in_use && (
                                                <Badge variant="secondary">
                                                    Sedang dipakai
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                            {style.prompt ||
                                                'Tidak ada instruksi prompt.'}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => edit(style)}
                                        >
                                            Ubah
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            disabled={style.is_in_use}
                                            onClick={() => destroy(style)}
                                        >
                                            Hapus
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>
            </div>
        </>
    );
}
