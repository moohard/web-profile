import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AiSuggestButton } from '@/components/admin/ai-suggest-button';
import LanguageTabs from '@/components/admin/language-tabs';
import type { LanguageOption } from '@/components/admin/language-tabs';
import { RichTextEditor } from '@/components/admin/rich-text-editor';
import TagPicker from '@/components/admin/tag-picker';
import InputError from '@/components/input-error';
import { MediaPicker } from '@/components/media/media-picker';
import { Button } from '@/components/ui/button';
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
import aiRoutes from '@/routes/admin/ai';
import postsRoutes, { index as postsIndex } from '@/routes/admin/posts';

type ContentTypeOption = {
    id: number;
    slug: string;
    name: string;
    writing_style_id: number | null;
};

type CategoryOption = {
    id: number;
    name: string;
};

type TagOption = {
    id: number;
    name: string;
};

type PostTranslationData = {
    language_id: number;
    title: string;
    slug: string;
    body: string | null;
    status: string;
    published_at: string | null;
    meta_title: string | null;
    meta_description: string | null;
};

type PostData = {
    id: number;
    type_id: number;
    category_id: number | null;
    featured_media_id: number | null;
    /** URL pratinjau (konversi `thumb`) — bukan bagian data yang disubmit. */
    featured_media_url: string | null;
    tag_ids: number[];
    /** Keyed by kode bahasa (mis. 'id', 'en') — bukan language_id. */
    translations: Record<string, PostTranslationData>;
};

type TranslationFormState = {
    language_id: number;
    title: string;
    slug: string;
    body: string;
    status: 'Draft' | 'Published';
    published_at: string;
    meta_title: string;
    meta_description: string;
};

type PostFormData = {
    type_id: number | null;
    category_id: number | null;
    tags: number[];
    /** Nama tag baru yang diketik di editor (create-on-type) — lihat TagPicker. */
    new_tags: string[];
    featured_media_id: number | null;
    translations: Record<number, TranslationFormState>;
};

/** Slugify ringan tanpa dependensi tambahan — dipakai untuk auto-slug dari judul. */
function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
}

/** Konversi datetime tersimpan ("YYYY-MM-DD HH:mm:ss") ke format <input type="datetime-local">. */
function toDatetimeLocal(value: string | null): string {
    if (!value) {
        return '';
    }

    return value.replace(' ', 'T').slice(0, 16);
}

function buildInitialTranslations(
    languages: LanguageOption[],
    post: PostData | null,
): Record<number, TranslationFormState> {
    const values: Record<number, TranslationFormState> = {};

    for (const lang of languages) {
        const existing = post?.translations[lang.code];

        values[lang.id] = {
            language_id: lang.id,
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            body: existing?.body ?? '',
            status: existing?.status === 'Published' ? 'Published' : 'Draft',
            published_at: toDatetimeLocal(existing?.published_at ?? null),
            meta_title: existing?.meta_title ?? '',
            meta_description: existing?.meta_description ?? '',
        };
    }

    return values;
}

export default function PostForm({
    post,
    languages,
    contentTypes,
    categories,
    tags,
}: {
    post: PostData | null;
    languages: LanguageOption[];
    contentTypes: ContentTypeOption[];
    categories: CategoryOption[];
    tags: TagOption[];
}) {
    const isEditing = post !== null;

    /** Bahasa sumber default untuk terjemahan AI — 'id' bila ada, jika tidak bahasa aktif pertama. */
    const defaultLanguage =
        languages.find((lang) => lang.code === 'id') ?? languages[0];

    const form = useForm<PostFormData>({
        type_id: post?.type_id ?? contentTypes[0]?.id ?? null,
        category_id: post?.category_id ?? null,
        tags: post?.tag_ids ?? [],
        new_tags: [],
        featured_media_id: post?.featured_media_id ?? null,
        translations: buildInitialTranslations(languages, post),
    });

    // URL pratinjau gambar unggulan — hanya tampilan, bukan bagian data yang
    // disubmit (submit memakai featured_media_id, lihat MediaPicker.onPick di bawah).
    const [featuredPreviewUrl, setFeaturedPreviewUrl] = useState<string | null>(
        post?.featured_media_url ?? null,
    );

    // Error dari backend memakai key index-array `translations.{index}.field` —
    // dipetakan berdasarkan posisi di `languages` (submit selalu menyertakan
    // seluruh bahasa yang judulnya terisi, sesuai urutan `languages`).
    const errorsAt = form.errors as Record<string, string | undefined>;
    const translationsError = errorsAt.translations;

    // Gaya bahasa dari jenis konten yang dipilih — dipakai sebagai konteks "Koreksi dengan AI".
    const selectedWritingStyleId =
        contentTypes.find((ct) => ct.id === form.data.type_id)
            ?.writing_style_id ?? null;

    function updateTranslation(
        languageId: number,
        patch: Partial<TranslationFormState>,
    ) {
        const current = form.data.translations[languageId];

        form.setData('translations', {
            ...form.data.translations,
            [languageId]: { ...current, ...patch },
        });
    }

    function submit(e: FormEvent) {
        e.preventDefault();

        form.transform((data) => ({
            ...data,
            translations: languages
                .filter(
                    (lang) => data.translations[lang.id]?.title.trim() !== '',
                )
                .map((lang) => {
                    const t = data.translations[lang.id];

                    return {
                        language_id: lang.id,
                        title: t.title,
                        slug: t.slug || null,
                        body: t.body || null,
                        status: t.status,
                        published_at: t.published_at || null,
                        meta_title: t.meta_title || null,
                        meta_description: t.meta_description || null,
                    };
                }),
        }));

        if (isEditing) {
            form.put(postsRoutes.update.url(post.id));
        } else {
            form.post(postsRoutes.store.url());
        }
    }

    return (
        <>
            <Head title={isEditing ? 'Ubah Post' : 'Tambah Post'} />
            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">
                    {isEditing ? 'Ubah post' : 'Tambah post'}
                </h1>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="space-y-2 lg:col-span-2">
                            <InputError message={translationsError} />

                            <LanguageTabs
                                languages={languages}
                                idPrefix="post"
                                renderPanel={(lang) => {
                                    const t = form.data.translations[lang.id];
                                    const idx = languages.findIndex(
                                        (l) => l.id === lang.id,
                                    );
                                    const fieldError = (
                                        field: string,
                                    ): string | undefined =>
                                        errorsAt[
                                            `translations.${idx}.${field}`
                                        ];

                                    return (
                                        <div className="space-y-3">
                                            <div className="space-y-1">
                                                <Label
                                                    htmlFor={`post-title-${lang.id}`}
                                                >
                                                    Judul ({lang.code})
                                                </Label>
                                                <Input
                                                    id={`post-title-${lang.id}`}
                                                    value={t.title}
                                                    onChange={(e) => {
                                                        const title =
                                                            e.target.value;
                                                        const autoSlug =
                                                            t.slug === '' ||
                                                            t.slug ===
                                                                slugify(
                                                                    t.title,
                                                                );

                                                        updateTranslation(
                                                            lang.id,
                                                            {
                                                                title,
                                                                slug: autoSlug
                                                                    ? slugify(
                                                                          title,
                                                                      )
                                                                    : t.slug,
                                                            },
                                                        );
                                                    }}
                                                    placeholder={`Judul dalam ${lang.name}`}
                                                />
                                                <InputError
                                                    message={fieldError(
                                                        'title',
                                                    )}
                                                />
                                                <div className="flex flex-wrap gap-2 pt-1">
                                                    {defaultLanguage && (
                                                        <AiSuggestButton
                                                            label="Terjemahkan dengan AI"
                                                            endpoint={aiRoutes.translate.url()}
                                                            payload={() => ({
                                                                source_text:
                                                                    form.data
                                                                        .translations[
                                                                        defaultLanguage
                                                                            .id
                                                                    ]?.title ??
                                                                    '',
                                                                source_locale:
                                                                    defaultLanguage.code,
                                                                target_locale:
                                                                    lang.code,
                                                            })}
                                                            onAccept={(text) =>
                                                                updateTranslation(
                                                                    lang.id,
                                                                    {
                                                                        title: text,
                                                                    },
                                                                )
                                                            }
                                                            disabled={
                                                                !form.data
                                                                    .translations[
                                                                    defaultLanguage
                                                                        .id
                                                                ]?.title
                                                            }
                                                        />
                                                    )}
                                                    <AiSuggestButton
                                                        label="Koreksi dengan AI"
                                                        endpoint={aiRoutes.refine.url()}
                                                        payload={() => ({
                                                            source_text:
                                                                form.data
                                                                    .translations[
                                                                    lang.id
                                                                ]?.title ?? '',
                                                            writing_style_id:
                                                                selectedWritingStyleId,
                                                        })}
                                                        onAccept={(text) =>
                                                            updateTranslation(
                                                                lang.id,
                                                                {
                                                                    title: text,
                                                                },
                                                            )
                                                        }
                                                        disabled={!t.title}
                                                    />
                                                </div>
                                            </div>

                                            <div className="space-y-1">
                                                <Label
                                                    htmlFor={`post-slug-${lang.id}`}
                                                >
                                                    Slug ({lang.code})
                                                </Label>
                                                <Input
                                                    id={`post-slug-${lang.id}`}
                                                    value={t.slug}
                                                    onChange={(e) =>
                                                        updateTranslation(
                                                            lang.id,
                                                            {
                                                                slug: e.target
                                                                    .value,
                                                            },
                                                        )
                                                    }
                                                    placeholder="otomatis-dari-judul"
                                                />
                                                <InputError
                                                    message={fieldError('slug')}
                                                />
                                            </div>

                                            <div className="space-y-1">
                                                <Label
                                                    htmlFor={`post-body-${lang.id}`}
                                                >
                                                    Konten ({lang.code})
                                                </Label>
                                                <RichTextEditor
                                                    id={`post-body-${lang.id}`}
                                                    value={t.body}
                                                    onChange={(body) =>
                                                        updateTranslation(
                                                            lang.id,
                                                            { body },
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={fieldError('body')}
                                                />
                                                <div className="flex flex-wrap gap-2 pt-1">
                                                    {defaultLanguage && (
                                                        <AiSuggestButton
                                                            label="Terjemahkan dengan AI"
                                                            endpoint={aiRoutes.translate.url()}
                                                            payload={() => ({
                                                                source_text:
                                                                    form.data
                                                                        .translations[
                                                                        defaultLanguage
                                                                            .id
                                                                    ]?.body ??
                                                                    '',
                                                                source_locale:
                                                                    defaultLanguage.code,
                                                                target_locale:
                                                                    lang.code,
                                                            })}
                                                            onAccept={(text) =>
                                                                updateTranslation(
                                                                    lang.id,
                                                                    {
                                                                        body: text,
                                                                    },
                                                                )
                                                            }
                                                            disabled={
                                                                !form.data
                                                                    .translations[
                                                                    defaultLanguage
                                                                        .id
                                                                ]?.body
                                                            }
                                                        />
                                                    )}
                                                    <AiSuggestButton
                                                        label="Koreksi dengan AI"
                                                        endpoint={aiRoutes.refine.url()}
                                                        payload={() => ({
                                                            source_text:
                                                                form.data
                                                                    .translations[
                                                                    lang.id
                                                                ]?.body ?? '',
                                                            writing_style_id:
                                                                selectedWritingStyleId,
                                                        })}
                                                        onAccept={(text) =>
                                                            updateTranslation(
                                                                lang.id,
                                                                {
                                                                    body: text,
                                                                },
                                                            )
                                                        }
                                                        disabled={!t.body}
                                                    />
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <div className="space-y-1">
                                                    <Label
                                                        htmlFor={`post-status-${lang.id}`}
                                                    >
                                                        Status ({lang.code})
                                                    </Label>
                                                    <Select
                                                        value={t.status}
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            updateTranslation(
                                                                lang.id,
                                                                {
                                                                    status:
                                                                        value ===
                                                                        'Published'
                                                                            ? 'Published'
                                                                            : 'Draft',
                                                                },
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger
                                                            id={`post-status-${lang.id}`}
                                                            className="w-full"
                                                        >
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="Draft">
                                                                Draft
                                                            </SelectItem>
                                                            <SelectItem value="Published">
                                                                Published
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={fieldError(
                                                            'status',
                                                        )}
                                                    />
                                                </div>

                                                <div className="space-y-1">
                                                    <Label
                                                        htmlFor={`post-published-at-${lang.id}`}
                                                    >
                                                        Tanggal publish (
                                                        {lang.code})
                                                    </Label>
                                                    <Input
                                                        id={`post-published-at-${lang.id}`}
                                                        type="datetime-local"
                                                        value={t.published_at}
                                                        onChange={(e) =>
                                                            updateTranslation(
                                                                lang.id,
                                                                {
                                                                    published_at:
                                                                        e.target
                                                                            .value,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={fieldError(
                                                            'published_at',
                                                        )}
                                                    />
                                                </div>
                                            </div>

                                            <div className="space-y-1">
                                                <Label
                                                    htmlFor={`post-meta-title-${lang.id}`}
                                                >
                                                    Meta title ({lang.code})
                                                </Label>
                                                <Input
                                                    id={`post-meta-title-${lang.id}`}
                                                    value={t.meta_title}
                                                    onChange={(e) =>
                                                        updateTranslation(
                                                            lang.id,
                                                            {
                                                                meta_title:
                                                                    e.target
                                                                        .value,
                                                            },
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={fieldError(
                                                        'meta_title',
                                                    )}
                                                />
                                            </div>

                                            <div className="space-y-1">
                                                <Label
                                                    htmlFor={`post-meta-description-${lang.id}`}
                                                >
                                                    Meta description (
                                                    {lang.code})
                                                </Label>
                                                <textarea
                                                    id={`post-meta-description-${lang.id}`}
                                                    value={t.meta_description}
                                                    onChange={(e) =>
                                                        updateTranslation(
                                                            lang.id,
                                                            {
                                                                meta_description:
                                                                    e.target
                                                                        .value,
                                                            },
                                                        )
                                                    }
                                                    rows={2}
                                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                />
                                                <InputError
                                                    message={fieldError(
                                                        'meta_description',
                                                    )}
                                                />
                                            </div>
                                        </div>
                                    );
                                }}
                            />
                        </div>

                        <div className="space-y-4">
                            <div className="space-y-1">
                                <Label htmlFor="post-type">Jenis konten</Label>
                                <Select
                                    value={
                                        form.data.type_id !== null
                                            ? String(form.data.type_id)
                                            : undefined
                                    }
                                    onValueChange={(value) =>
                                        form.setData('type_id', Number(value))
                                    }
                                >
                                    <SelectTrigger
                                        id="post-type"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih jenis konten" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {contentTypes.map((ct) => (
                                            <SelectItem
                                                key={ct.id}
                                                value={String(ct.id)}
                                            >
                                                {ct.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.type_id} />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="post-category">Kategori</Label>
                                <Select
                                    value={
                                        form.data.category_id !== null
                                            ? String(form.data.category_id)
                                            : 'none'
                                    }
                                    onValueChange={(value) =>
                                        form.setData(
                                            'category_id',
                                            value === 'none'
                                                ? null
                                                : Number(value),
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="post-category"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Tanpa kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Tanpa kategori
                                        </SelectItem>
                                        {categories.map((category) => (
                                            <SelectItem
                                                key={category.id}
                                                value={String(category.id)}
                                            >
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.category_id} />
                            </div>

                            <div className="space-y-1">
                                <Label>Tag</Label>
                                <TagPicker
                                    options={tags}
                                    selectedIds={form.data.tags}
                                    newNames={form.data.new_tags}
                                    onSelectedChange={(ids) =>
                                        form.setData('tags', ids)
                                    }
                                    onNewNamesChange={(names) =>
                                        form.setData('new_tags', names)
                                    }
                                />
                                <InputError message={form.errors.tags} />
                                <InputError message={form.errors.new_tags} />
                            </div>

                            <div className="space-y-1">
                                <Label>Gambar unggulan</Label>
                                <div className="space-y-2">
                                    {featuredPreviewUrl && (
                                        <img
                                            src={featuredPreviewUrl}
                                            alt="Pratinjau gambar unggulan"
                                            className="aspect-video w-full rounded-md border object-cover"
                                        />
                                    )}
                                    <div className="flex gap-2">
                                        <MediaPicker
                                            onPick={(mediaId, url) => {
                                                form.setData(
                                                    'featured_media_id',
                                                    mediaId,
                                                );
                                                setFeaturedPreviewUrl(url);
                                            }}
                                        />
                                        {featuredPreviewUrl && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    form.setData(
                                                        'featured_media_id',
                                                        null,
                                                    );
                                                    setFeaturedPreviewUrl(null);
                                                }}
                                            >
                                                Hapus
                                            </Button>
                                        )}
                                    </div>
                                </div>
                                <InputError
                                    message={form.errors.featured_media_id}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={postsRoutes.index.url()}>Batal</a>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PostForm.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Posts',
            href: postsIndex(),
        },
    ],
};
