import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes/admin';
import {
    destroy,
    index as mediaIndex,
    store,
    update,
} from '@/routes/admin/media';

type Locale = { code: string; name: string };

type MediaItem = {
    id: number;
    file_name: string;
    mime_type: string;
    size: number;
    collection_name: string;
    model_type: string;
    model_id: number;
    url: string;
    thumb_url: string;
    alt: string;
    alt_overrides: Record<string, string>;
};

type MediaPage = {
    data: MediaItem[];
    current_page: number;
    last_page: number;
    total: number;
};

type UploadForm = {
    file: File | null;
    model_type: 'Post' | 'Page' | 'Testimonial';
    model_id: number;
    collection: string;
    alt: string;
};

type AltForm = {
    alt: string;
    alt_overrides: Record<string, string>;
};

/** Kartu media dengan editor alt-text (satu alt default + override per bahasa). */
function MediaCard({ item, locales }: { item: MediaItem; locales: Locale[] }) {
    const altForm = useForm<AltForm>({
        alt: item.alt ?? '',
        alt_overrides: item.alt_overrides ?? {},
    });
    const [showOverrides, setShowOverrides] = useState(false);

    function saveAlt(e: React.FormEvent) {
        e.preventDefault();
        altForm.patch(update.url(item.id), {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function deleteMedia() {
        if (!confirm('Hapus media ini?')) {
            return;
        }

        router.delete(destroy.url(item.id), { preserveScroll: true });
    }

    return (
        <div className="space-y-2 rounded-lg border p-2">
            <img
                src={item.thumb_url || item.url}
                alt={item.alt || item.file_name}
                className="aspect-square w-full rounded object-cover"
                loading="lazy"
            />
            <p className="truncate text-xs" title={item.file_name}>
                {item.file_name}
            </p>
            <p className="truncate text-[10px] text-muted-foreground">
                {item.model_type}#{item.model_id} · {item.collection_name}
            </p>

            <form onSubmit={saveAlt} className="space-y-1">
                <Label htmlFor={`alt-${item.id}`} className="text-[10px]">
                    Alt text
                </Label>
                <Input
                    id={`alt-${item.id}`}
                    value={altForm.data.alt}
                    onChange={(e) => altForm.setData('alt', e.target.value)}
                    placeholder="Teks alternatif"
                    className="h-8 text-xs"
                />
                <InputError message={altForm.errors.alt} />

                {locales.length > 1 && (
                    <button
                        type="button"
                        className="text-[10px] text-muted-foreground underline"
                        onClick={() => setShowOverrides((v) => !v)}
                    >
                        {showOverrides ? 'Sembunyikan' : 'Override per bahasa'}
                    </button>
                )}

                {showOverrides &&
                    locales.map((l) => (
                        <Input
                            key={l.code}
                            value={altForm.data.alt_overrides[l.code] ?? ''}
                            onChange={(e) =>
                                altForm.setData('alt_overrides', {
                                    ...altForm.data.alt_overrides,
                                    [l.code]: e.target.value,
                                })
                            }
                            placeholder={`Alt (${l.name})`}
                            className="h-8 text-xs"
                        />
                    ))}

                <Button
                    type="submit"
                    size="sm"
                    variant="outline"
                    className="w-full"
                    disabled={altForm.processing}
                >
                    {altForm.processing ? 'Menyimpan…' : 'Simpan alt'}
                </Button>
            </form>

            <Button
                type="button"
                variant="destructive"
                size="sm"
                className="w-full"
                onClick={deleteMedia}
            >
                Hapus
            </Button>
        </div>
    );
}

export default function MediaIndex({
    media,
    locales,
}: {
    media: MediaPage;
    locales: Locale[];
}) {
    const form = useForm<UploadForm>({
        file: null,
        model_type: 'Post',
        model_id: 1,
        collection: 'featured_image',
        alt: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(store.url(), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => form.reset('file', 'alt'),
        });
    }

    return (
        <>
            <Head title="Media" />
            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">Media</h1>

                <form
                    onSubmit={submit}
                    className="flex flex-wrap items-end gap-3 rounded-lg border p-4"
                    encType="multipart/form-data"
                >
                    <div className="space-y-1">
                        <Label htmlFor="media-file">File</Label>
                        <Input
                            id="media-file"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            onChange={(e) =>
                                form.setData(
                                    'file',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                        />
                        <InputError message={form.errors.file} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="media-model-type">Model</Label>
                        <select
                            id="media-model-type"
                            value={form.data.model_type}
                            onChange={(e) =>
                                form.setData(
                                    'model_type',
                                    e.target.value as UploadForm['model_type'],
                                )
                            }
                            className="h-9 rounded-md border border-input bg-background px-2 text-sm"
                        >
                            <option value="Post">Post</option>
                            <option value="Page">Page</option>
                            <option value="Testimonial">Testimonial</option>
                        </select>
                        <InputError message={form.errors.model_type} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="media-model-id">ID model</Label>
                        <Input
                            id="media-model-id"
                            type="number"
                            min={1}
                            value={form.data.model_id}
                            onChange={(e) =>
                                form.setData(
                                    'model_id',
                                    Number.parseInt(e.target.value, 10) || 0,
                                )
                            }
                            className="w-28"
                        />
                        <InputError message={form.errors.model_id} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="media-collection">Collection</Label>
                        <Input
                            id="media-collection"
                            value={form.data.collection}
                            onChange={(e) =>
                                form.setData('collection', e.target.value)
                            }
                            placeholder="featured_image"
                            className="w-40"
                        />
                        <InputError message={form.errors.collection} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="media-alt">Alt text</Label>
                        <Input
                            id="media-alt"
                            value={form.data.alt}
                            onChange={(e) =>
                                form.setData('alt', e.target.value)
                            }
                            placeholder="Teks alternatif"
                            className="w-56"
                        />
                        <InputError message={form.errors.alt} />
                    </div>

                    <Button
                        type="submit"
                        disabled={form.processing || !form.data.file}
                    >
                        {form.processing ? 'Mengunggah…' : 'Upload'}
                    </Button>

                    {form.progress && (
                        <progress
                            value={form.progress.percentage}
                            max={100}
                            className="h-2 w-full"
                        >
                            {form.progress.percentage}%
                        </progress>
                    )}
                </form>

                {media.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada media. Unggah file di atas.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
                        {media.data.map((item) => (
                            <MediaCard
                                key={item.id}
                                item={item}
                                locales={locales}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

MediaIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Media',
            href: mediaIndex(),
        },
    ],
};
