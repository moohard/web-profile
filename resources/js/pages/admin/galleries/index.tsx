import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { ComponentProps } from 'react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import LanguageTabs from '@/components/admin/language-tabs';
import type { LanguageOption } from '@/components/admin/language-tabs';
import InputError from '@/components/input-error';
import { MediaPicker } from '@/components/media/media-picker';
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
import galleriesRoutes, {
    index as galleriesIndex,
} from '@/routes/admin/galleries';

type GalleryTranslation = {
    language_id: number;
    title: string;
    description: string | null;
};

type GalleryImage = {
    id: number;
    path: string;
    sort_order: number;
    captions: Array<{ language_id: number; caption: string | null }>;
};

type Gallery = {
    id: number;
    slug: string;
    is_active: boolean;
    translations: GalleryTranslation[];
    images: GalleryImage[];
};

type ImageFormData = {
    id?: number;
    path: string;
    captions: Record<number, string>;
};

type GalleryFormData = {
    slug: string;
    is_active: boolean;
    translations: Record<number, { title: string; description: string }>;
    images: ImageFormData[];
};

function galleryTitle(gallery: Gallery, languages: LanguageOption[]): string {
    const primaryLanguageId = languages[0]?.id;
    const translation =
        gallery.translations.find(
            (item) => item.language_id === primaryLanguageId,
        ) ?? gallery.translations[0];

    return translation?.title ?? '(tanpa judul)';
}

function initialTranslations(
    languages: LanguageOption[],
    gallery?: Gallery,
): GalleryFormData['translations'] {
    return Object.fromEntries(
        languages.map((language) => {
            const translation = gallery?.translations.find(
                (item) => item.language_id === language.id,
            );

            return [
                language.id,
                {
                    title: translation?.title ?? '',
                    description: translation?.description ?? '',
                },
            ];
        }),
    );
}

function initialImages(
    languages: LanguageOption[],
    gallery?: Gallery,
): ImageFormData[] {
    return (gallery?.images ?? []).map((image) => ({
        id: image.id,
        path: image.path,
        captions: Object.fromEntries(
            languages.map((language) => [
                language.id,
                image.captions.find(
                    (caption) => caption.language_id === language.id,
                )?.caption ?? '',
            ]),
        ),
    }));
}

function GalleryFormDialog({
    gallery,
    languages,
    onClose,
}: {
    gallery?: Gallery;
    languages: LanguageOption[];
    onClose: () => void;
}) {
    const isEditing = gallery !== undefined;
    const form = useForm<GalleryFormData>({
        slug: gallery?.slug ?? '',
        is_active: gallery?.is_active ?? true,
        translations: initialTranslations(languages, gallery),
        images: initialImages(languages, gallery),
    });
    const errors = form.errors as Record<string, string | undefined>;

    function updateImage(index: number, image: ImageFormData) {
        form.setData(
            'images',
            form.data.images.map((current, currentIndex) =>
                currentIndex === index ? image : current,
            ),
        );
    }

    function moveImage(index: number, direction: -1 | 1) {
        const targetIndex = index + direction;

        if (targetIndex < 0 || targetIndex >= form.data.images.length) {
            return;
        }

        const images = [...form.data.images];
        [images[index], images[targetIndex]] = [
            images[targetIndex],
            images[index],
        ];
        form.setData('images', images);
    }

    function submit(
        event: Parameters<NonNullable<ComponentProps<'form'>['onSubmit']>>[0],
    ) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            translations: languages.map((language) => ({
                language_id: language.id,
                title: data.translations[language.id]?.title ?? '',
                description:
                    data.translations[language.id]?.description || null,
            })),
            images: data.images.map((image) => ({
                ...(image.id === undefined ? {} : { id: image.id }),
                path: image.path,
                captions: languages.map((language) => ({
                    language_id: language.id,
                    caption: image.captions[language.id] || null,
                })),
            })),
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEditing) {
            form.put(galleriesRoutes.update.url(gallery.id), options);
        } else {
            form.post(galleriesRoutes.store.url(), options);
        }
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] max-w-4xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Ubah galeri' : 'Tambah galeri'}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-6">
                    <LanguageTabs
                        languages={languages}
                        idPrefix="gallery"
                        renderPanel={(language) => {
                            const translation =
                                form.data.translations[language.id];
                            const languageIndex = languages.findIndex(
                                (item) => item.id === language.id,
                            );

                            return (
                                <div className="space-y-3">
                                    <div className="space-y-1">
                                        <Label
                                            htmlFor={`gallery-title-${language.id}`}
                                        >
                                            Judul ({language.code})
                                        </Label>
                                        <Input
                                            id={`gallery-title-${language.id}`}
                                            value={translation?.title ?? ''}
                                            onChange={(event) =>
                                                form.setData('translations', {
                                                    ...form.data.translations,
                                                    [language.id]: {
                                                        ...translation,
                                                        title: event.target
                                                            .value,
                                                    },
                                                })
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `translations.${languageIndex}.title`
                                                ]
                                            }
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label
                                            htmlFor={`gallery-description-${language.id}`}
                                        >
                                            Deskripsi ({language.code})
                                        </Label>
                                        <textarea
                                            id={`gallery-description-${language.id}`}
                                            value={
                                                translation?.description ?? ''
                                            }
                                            onChange={(event) =>
                                                form.setData('translations', {
                                                    ...form.data.translations,
                                                    [language.id]: {
                                                        ...translation,
                                                        description:
                                                            event.target.value,
                                                    },
                                                })
                                            }
                                            rows={3}
                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        />
                                    </div>
                                </div>
                            );
                        }}
                    />

                    <div className="space-y-1">
                        <Label htmlFor="gallery-slug">Slug</Label>
                        <Input
                            id="gallery-slug"
                            value={form.data.slug}
                            onChange={(event) =>
                                form.setData('slug', event.target.value)
                            }
                            placeholder="acara-2026"
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <label className="flex items-center gap-2 text-sm font-medium">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(event) =>
                                form.setData('is_active', event.target.checked)
                            }
                        />
                        Galeri aktif
                    </label>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="font-medium">Gambar</h2>
                            <MediaPicker
                                onPick={(_mediaId, url) =>
                                    form.setData('images', [
                                        ...form.data.images,
                                        {
                                            path: url,
                                            captions: Object.fromEntries(
                                                languages.map((language) => [
                                                    language.id,
                                                    '',
                                                ]),
                                            ),
                                        },
                                    ])
                                }
                            />
                        </div>
                        <InputError message={form.errors.images} />

                        {form.data.images.map((image, index) => (
                            <div
                                key={image.id ?? `${image.path}-${index}`}
                                className="space-y-3 rounded-md border p-3"
                            >
                                <div className="flex flex-col gap-3 sm:flex-row">
                                    <img
                                        src={image.path}
                                        alt="Pratinjau gambar galeri"
                                        className="h-24 w-32 rounded object-cover"
                                    />
                                    <div className="flex flex-wrap content-start gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={index === 0}
                                            onClick={() => moveImage(index, -1)}
                                        >
                                            Naik
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={
                                                index ===
                                                form.data.images.length - 1
                                            }
                                            onClick={() => moveImage(index, 1)}
                                        >
                                            Turun
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="destructive"
                                            onClick={() =>
                                                form.setData(
                                                    'images',
                                                    form.data.images.filter(
                                                        (_, currentIndex) =>
                                                            currentIndex !==
                                                            index,
                                                    ),
                                                )
                                            }
                                        >
                                            Hapus
                                        </Button>
                                    </div>
                                </div>

                                <LanguageTabs
                                    languages={languages}
                                    idPrefix={`gallery-image-${image.id ?? index}`}
                                    renderPanel={(language) => (
                                        <div className="space-y-1">
                                            <Label
                                                htmlFor={`gallery-image-${index}-caption-${language.id}`}
                                            >
                                                Caption ({language.code})
                                            </Label>
                                            <Input
                                                id={`gallery-image-${index}-caption-${language.id}`}
                                                value={
                                                    image.captions[
                                                        language.id
                                                    ] ?? ''
                                                }
                                                onChange={(event) =>
                                                    updateImage(index, {
                                                        ...image,
                                                        captions: {
                                                            ...image.captions,
                                                            [language.id]:
                                                                event.target
                                                                    .value,
                                                        },
                                                    })
                                                }
                                            />
                                        </div>
                                    )}
                                />
                            </div>
                        ))}
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

export default function GalleriesIndex({
    galleries,
    languages,
}: {
    galleries: Gallery[];
    languages: LanguageOption[];
}) {
    const [dialogFor, setDialogFor] = useState<number | 'new' | null>(null);
    const editingGallery =
        typeof dialogFor === 'number'
            ? galleries.find((gallery) => gallery.id === dialogFor)
            : undefined;
    const columns: DataTableColumn<Gallery>[] = [
        {
            key: 'title',
            header: 'Judul',
            render: (gallery) => galleryTitle(gallery, languages),
        },
        {
            key: 'slug',
            header: 'Slug',
            render: (gallery) => (
                <code className="text-xs">{gallery.slug}</code>
            ),
        },
        {
            key: 'images',
            header: 'Gambar',
            render: (gallery) => gallery.images.length,
        },
        {
            key: 'status',
            header: 'Status',
            render: (gallery) => (gallery.is_active ? 'Aktif' : 'Nonaktif'),
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (gallery) => (
                <div className="flex justify-end gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => setDialogFor(gallery.id)}
                    >
                        Ubah
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={() => {
                            if (
                                confirm(
                                    `Hapus galeri "${galleryTitle(gallery, languages)}"?`,
                                )
                            ) {
                                router.delete(
                                    galleriesRoutes.destroy.url(gallery.id),
                                    {
                                        preserveScroll: true,
                                    },
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
            <Head title="Galeri" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">Galeri</h1>
                    <Button type="button" onClick={() => setDialogFor('new')}>
                        Tambah galeri
                    </Button>
                </div>
                <DataTable
                    columns={columns}
                    data={galleries}
                    rowKey={(gallery) => gallery.id}
                    emptyMessage="Belum ada galeri. Tambahkan galeri pertama."
                />
            </div>
            {dialogFor !== null && (
                <GalleryFormDialog
                    key={dialogFor}
                    gallery={editingGallery}
                    languages={languages}
                    onClose={() => setDialogFor(null)}
                />
            )}
        </>
    );
}

GalleriesIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Galeri', href: galleriesIndex() },
    ],
};
