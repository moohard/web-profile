import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AiSuggestButton } from '@/components/admin/ai-suggest-button';
import LanguageTabs from '@/components/admin/language-tabs';
import type { LanguageOption } from '@/components/admin/language-tabs';
import InputError from '@/components/input-error';
import { MediaPicker } from '@/components/media/media-picker';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { markupConform } from '@/routes/admin/ai';
import pagesRoutes, { index as pagesIndex } from '@/routes/admin/pages';

type TemplateOption = {
    key: string;
    label: string;
};

type PageTranslationData = {
    language_id: number;
    title: string;
    slug: string;
    content: string;
    hero_heading: string | null;
    hero_subheading: string | null;
    hero_cta_text: string | null;
    hero_cta_link: string | null;
    status: string;
    meta_title: string | null;
    meta_description: string | null;
};

type PageData = {
    id: number;
    mode: 'Code' | 'Template';
    template_key: string;
    hero_enabled: boolean;
    hero_image: string | null;
    sidebar_enabled: boolean;
    /** Keyed by kode bahasa (mis. 'id', 'en') — bukan language_id. */
    translations: Record<string, PageTranslationData>;
};

type TranslationFormState = {
    language_id: number;
    title: string;
    slug: string;
    content: string;
    hero_heading: string;
    hero_subheading: string;
    hero_cta_text: string;
    hero_cta_link: string;
    status: 'Draft' | 'Published';
    meta_title: string;
    meta_description: string;
};

type PageFormData = {
    mode: 'Code' | 'Template';
    template_key: string;
    hero_enabled: boolean;
    hero_image: string;
    sidebar_enabled: boolean;
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

function buildInitialTranslations(
    languages: LanguageOption[],
    page: PageData | null,
): Record<number, TranslationFormState> {
    const values: Record<number, TranslationFormState> = {};

    for (const lang of languages) {
        const existing = page?.translations[lang.code];

        values[lang.id] = {
            language_id: lang.id,
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            content: existing?.content ?? '',
            hero_heading: existing?.hero_heading ?? '',
            hero_subheading: existing?.hero_subheading ?? '',
            hero_cta_text: existing?.hero_cta_text ?? '',
            hero_cta_link: existing?.hero_cta_link ?? '',
            status: existing?.status === 'Published' ? 'Published' : 'Draft',
            meta_title: existing?.meta_title ?? '',
            meta_description: existing?.meta_description ?? '',
        };
    }

    return values;
}

export default function PageForm({
    page,
    languages,
    canUseCodeMode,
    templateOptions,
}: {
    page: PageData | null;
    languages: LanguageOption[];
    canUseCodeMode: boolean;
    templateOptions: TemplateOption[];
}) {
    const isEditing = page !== null;
    const [activeLanguageId, setActiveLanguageId] = useState<number>(
        languages[0]?.id ?? 0,
    );

    const form = useForm<PageFormData>({
        mode: page?.mode ?? 'Template',
        template_key:
            page?.template_key ?? templateOptions[0]?.key ?? 'default',
        hero_enabled: page?.hero_enabled ?? false,
        hero_image: page?.hero_image ?? '',
        sidebar_enabled: page?.sidebar_enabled ?? false,
        translations: buildInitialTranslations(languages, page),
    });

    // Error dari backend memakai key index-array `translations.{index}.field` —
    // dipetakan berdasarkan posisi di `languages` (submit selalu menyertakan
    // seluruh bahasa yang judulnya terisi, sesuai urutan `languages`).
    const errorsAt = form.errors as Record<string, string | undefined>;
    const translationsError = errorsAt.translations;

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
                        content: t.content || null,
                        hero_heading: t.hero_heading || null,
                        hero_subheading: t.hero_subheading || null,
                        hero_cta_text: t.hero_cta_text || null,
                        hero_cta_link: t.hero_cta_link || null,
                        status: t.status,
                        meta_title: t.meta_title || null,
                        meta_description: t.meta_description || null,
                    };
                }),
        }));

        if (isEditing) {
            form.put(pagesRoutes.update.url(page.id));
        } else {
            form.post(pagesRoutes.store.url());
        }
    }

    const activeTranslation = form.data.translations[activeLanguageId];

    return (
        <>
            <Head title={isEditing ? 'Ubah Halaman' : 'Tambah Halaman'} />
            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">
                    {isEditing ? 'Ubah halaman' : 'Tambah halaman'}
                </h1>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="space-y-4 lg:col-span-2">
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <Label htmlFor="page-mode">Mode</Label>
                                    <Select
                                        value={form.data.mode}
                                        onValueChange={(value) =>
                                            form.setData(
                                                'mode',
                                                value === 'Code'
                                                    ? 'Code'
                                                    : 'Template',
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="page-mode"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Template">
                                                Template
                                            </SelectItem>
                                            {canUseCodeMode && (
                                                <SelectItem value="Code">
                                                    Code
                                                </SelectItem>
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.mode} />
                                </div>

                                {form.data.mode === 'Template' && (
                                    <div className="space-y-1">
                                        <Label htmlFor="page-template-key">
                                            Template
                                        </Label>
                                        <Select
                                            value={form.data.template_key}
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'template_key',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id="page-template-key"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {templateOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.key}
                                                            value={option.key}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={form.errors.template_key}
                                        />
                                    </div>
                                )}
                            </div>

                            <InputError message={translationsError} />

                            <LanguageTabs
                                languages={languages}
                                idPrefix="page"
                                active={activeLanguageId}
                                onActiveChange={setActiveLanguageId}
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
                                                    htmlFor={`page-title-${lang.id}`}
                                                >
                                                    Judul ({lang.code})
                                                </Label>
                                                <Input
                                                    id={`page-title-${lang.id}`}
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
                                            </div>

                                            {form.data.mode === 'Code' ? (
                                                <div className="space-y-1">
                                                    <Label
                                                        htmlFor={`page-content-${lang.id}`}
                                                    >
                                                        Konten HTML ({lang.code}
                                                        )
                                                    </Label>
                                                    <textarea
                                                        id={`page-content-${lang.id}`}
                                                        value={t.content}
                                                        onChange={(e) =>
                                                            updateTranslation(
                                                                lang.id,
                                                                {
                                                                    content:
                                                                        e.target
                                                                            .value,
                                                                },
                                                            )
                                                        }
                                                        rows={14}
                                                        className="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm"
                                                        placeholder={`Markup HTML dalam ${lang.name}`}
                                                    />
                                                    <InputError
                                                        message={fieldError(
                                                            'content',
                                                        )}
                                                    />
                                                    {canUseCodeMode && (
                                                        <AiSuggestButton
                                                            label="Sesuaikan markup (AI)"
                                                            endpoint={markupConform.url()}
                                                            payload={() => ({
                                                                source_html:
                                                                    t.content,
                                                            })}
                                                            onAccept={(html) =>
                                                                updateTranslation(
                                                                    lang.id,
                                                                    {
                                                                        content:
                                                                            html,
                                                                    },
                                                                )
                                                            }
                                                            disabled={
                                                                t.content.trim() ===
                                                                ''
                                                            }
                                                        />
                                                    )}
                                                </div>
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    Konten halaman ini mengikuti
                                                    template yang dipilih.
                                                </p>
                                            )}
                                        </div>
                                    );
                                }}
                            />
                        </div>

                        <div className="space-y-4">
                            <div className="space-y-2 rounded-md border p-3">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="page-hero-enabled"
                                        checked={form.data.hero_enabled}
                                        onCheckedChange={(checked) =>
                                            form.setData(
                                                'hero_enabled',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="page-hero-enabled"
                                        className="font-normal"
                                    >
                                        Aktifkan hero
                                    </Label>
                                </div>

                                {form.data.hero_enabled && (
                                    <>
                                        <div className="space-y-1">
                                            {form.data.hero_image && (
                                                <img
                                                    src={form.data.hero_image}
                                                    alt="Pratinjau hero"
                                                    className="aspect-video w-full rounded-md border object-cover"
                                                />
                                            )}
                                            <div className="flex gap-2">
                                                <MediaPicker
                                                    onPick={(_mediaId, url) =>
                                                        form.setData(
                                                            'hero_image',
                                                            url,
                                                        )
                                                    }
                                                />
                                                {form.data.hero_image && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            form.setData(
                                                                'hero_image',
                                                                '',
                                                            )
                                                        }
                                                    >
                                                        Hapus
                                                    </Button>
                                                )}
                                            </div>
                                            <InputError
                                                message={form.errors.hero_image}
                                            />
                                        </div>

                                        {activeTranslation && (
                                            <div className="space-y-2">
                                                <div className="space-y-1">
                                                    <Label
                                                        htmlFor={`page-hero-heading-${activeLanguageId}`}
                                                    >
                                                        Judul hero (
                                                        {
                                                            languages.find(
                                                                (l) =>
                                                                    l.id ===
                                                                    activeLanguageId,
                                                            )?.code
                                                        }
                                                        )
                                                    </Label>
                                                    <Input
                                                        id={`page-hero-heading-${activeLanguageId}`}
                                                        value={
                                                            activeTranslation.hero_heading
                                                        }
                                                        onChange={(e) =>
                                                            updateTranslation(
                                                                activeLanguageId,
                                                                {
                                                                    hero_heading:
                                                                        e.target
                                                                            .value,
                                                                },
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="space-y-1">
                                                    <Label
                                                        htmlFor={`page-hero-subheading-${activeLanguageId}`}
                                                    >
                                                        Subjudul hero
                                                    </Label>
                                                    <Input
                                                        id={`page-hero-subheading-${activeLanguageId}`}
                                                        value={
                                                            activeTranslation.hero_subheading
                                                        }
                                                        onChange={(e) =>
                                                            updateTranslation(
                                                                activeLanguageId,
                                                                {
                                                                    hero_subheading:
                                                                        e.target
                                                                            .value,
                                                                },
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="grid grid-cols-2 gap-2">
                                                    <div className="space-y-1">
                                                        <Label
                                                            htmlFor={`page-hero-cta-text-${activeLanguageId}`}
                                                        >
                                                            Teks CTA
                                                        </Label>
                                                        <Input
                                                            id={`page-hero-cta-text-${activeLanguageId}`}
                                                            value={
                                                                activeTranslation.hero_cta_text
                                                            }
                                                            onChange={(e) =>
                                                                updateTranslation(
                                                                    activeLanguageId,
                                                                    {
                                                                        hero_cta_text:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label
                                                            htmlFor={`page-hero-cta-link-${activeLanguageId}`}
                                                        >
                                                            Tautan CTA
                                                        </Label>
                                                        <Input
                                                            id={`page-hero-cta-link-${activeLanguageId}`}
                                                            value={
                                                                activeTranslation.hero_cta_link
                                                            }
                                                            onChange={(e) =>
                                                                updateTranslation(
                                                                    activeLanguageId,
                                                                    {
                                                                        hero_cta_link:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>

                            <div className="flex items-center gap-2 rounded-md border p-3">
                                <Checkbox
                                    id="page-sidebar-enabled"
                                    checked={form.data.sidebar_enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'sidebar_enabled',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label
                                    htmlFor="page-sidebar-enabled"
                                    className="font-normal"
                                >
                                    Aktifkan sidebar
                                </Label>
                            </div>

                            {activeTranslation && (
                                <div className="space-y-3 rounded-md border p-3">
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Slug, status &amp; SEO (
                                        {
                                            languages.find(
                                                (l) =>
                                                    l.id === activeLanguageId,
                                            )?.code
                                        }
                                        )
                                    </p>

                                    <div className="space-y-1">
                                        <Label
                                            htmlFor={`page-slug-${activeLanguageId}`}
                                        >
                                            Slug
                                        </Label>
                                        <Input
                                            id={`page-slug-${activeLanguageId}`}
                                            value={activeTranslation.slug}
                                            onChange={(e) =>
                                                updateTranslation(
                                                    activeLanguageId,
                                                    {
                                                        slug: e.target.value,
                                                    },
                                                )
                                            }
                                            placeholder="otomatis-dari-judul"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label
                                            htmlFor={`page-status-${activeLanguageId}`}
                                        >
                                            Status
                                        </Label>
                                        <Select
                                            value={activeTranslation.status}
                                            onValueChange={(value) =>
                                                updateTranslation(
                                                    activeLanguageId,
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
                                                id={`page-status-${activeLanguageId}`}
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
                                    </div>

                                    <div className="space-y-1">
                                        <Label
                                            htmlFor={`page-meta-title-${activeLanguageId}`}
                                        >
                                            Meta title
                                        </Label>
                                        <Input
                                            id={`page-meta-title-${activeLanguageId}`}
                                            value={activeTranslation.meta_title}
                                            onChange={(e) =>
                                                updateTranslation(
                                                    activeLanguageId,
                                                    {
                                                        meta_title:
                                                            e.target.value,
                                                    },
                                                )
                                            }
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label
                                            htmlFor={`page-meta-description-${activeLanguageId}`}
                                        >
                                            Meta description
                                        </Label>
                                        <textarea
                                            id={`page-meta-description-${activeLanguageId}`}
                                            value={
                                                activeTranslation.meta_description
                                            }
                                            onChange={(e) =>
                                                updateTranslation(
                                                    activeLanguageId,
                                                    {
                                                        meta_description:
                                                            e.target.value,
                                                    },
                                                )
                                            }
                                            rows={2}
                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={pagesRoutes.index.url()}>Batal</a>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PageForm.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Halaman',
            href: pagesIndex(),
        },
    ],
};
