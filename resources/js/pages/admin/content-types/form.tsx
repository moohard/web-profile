import { Head, useForm } from '@inertiajs/react';
import LanguageTabs from '@/components/admin/language-tabs';
import type { LanguageOption } from '@/components/admin/language-tabs';
import InputError from '@/components/input-error';
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

type WritingStyleOption = {
    id: number;
    name: string;
};

type ContentTypeFormData = {
    slug: string;
    icon: string;
    writing_style_id: number | null;
    archive_template_key: string;
    single_template_key: string;
    is_active: boolean;
    sort_order: number;
    translations: Record<number, string>;
    descriptions: Record<number, string>;
};

function buildInitialTranslations(
    languages: LanguageOption[],
    contentType: ContentType | null,
): Record<number, string> {
    const values: Record<number, string> = {};

    for (const lang of languages) {
        const existing = contentType?.translations.find(
            (t) => t.language_id === lang.id,
        );
        values[lang.id] = existing?.name ?? '';
    }

    return values;
}

function buildInitialDescriptions(
    languages: LanguageOption[],
    contentType: ContentType | null,
): Record<number, string> {
    const values: Record<number, string> = {};

    for (const lang of languages) {
        const existing = contentType?.translations.find(
            (t) => t.language_id === lang.id,
        );
        values[lang.id] = existing?.description ?? '';
    }

    return values;
}

export default function ContentTypeForm({
    contentType,
    languages,
    writingStyles,
}: {
    contentType: ContentType | null;
    languages: LanguageOption[];
    writingStyles: WritingStyleOption[];
}) {
    const isEditing = contentType !== null;

    const form = useForm<ContentTypeFormData>({
        slug: contentType?.slug ?? '',
        icon: contentType?.icon ?? '',
        writing_style_id: contentType?.writing_style_id ?? null,
        archive_template_key: contentType?.archive_template_key ?? 'default',
        single_template_key: contentType?.single_template_key ?? 'default',
        is_active: contentType?.is_active ?? true,
        sort_order: contentType?.sort_order ?? 0,
        translations: buildInitialTranslations(languages, contentType),
        descriptions: buildInitialDescriptions(languages, contentType),
    });

    // Peta index array (posisi di `languages`) → error, karena backend mengirim
    // error dengan key `translations.{index}.name`, bukan berdasarkan language_id.
    const nameErrors: Record<number, string | undefined> = {};
    const descriptionErrors: Record<number, string | undefined> = {};
    languages.forEach((lang, i) => {
        const errors = form.errors as Record<string, string | undefined>;
        nameErrors[lang.id] = errors[`translations.${i}.name`];
        descriptionErrors[lang.id] = errors[`translations.${i}.description`];
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();

        form.transform((data) => ({
            ...data,
            translations: languages.map((lang) => ({
                language_id: lang.id,
                name: data.translations[lang.id] ?? '',
                description: data.descriptions[lang.id] || null,
            })),
        }));

        if (isEditing) {
            form.put(contentTypesRoutes.update.url(contentType.id));
        } else {
            form.post(contentTypesRoutes.store.url());
        }
    }

    return (
        <>
            <Head
                title={isEditing ? 'Ubah Jenis Konten' : 'Tambah Jenis Konten'}
            />
            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">
                    {isEditing ? 'Ubah jenis konten' : 'Tambah jenis konten'}
                </h1>

                <form onSubmit={submit} className="max-w-2xl space-y-4">
                    <LanguageTabs
                        languages={languages}
                        values={form.data.translations}
                        errors={nameErrors}
                        onChange={(languageId, value) =>
                            form.setData('translations', {
                                ...form.data.translations,
                                [languageId]: value,
                            })
                        }
                        descriptionValues={form.data.descriptions}
                        descriptionErrors={descriptionErrors}
                        onDescriptionChange={(languageId, value) =>
                            form.setData('descriptions', {
                                ...form.data.descriptions,
                                [languageId]: value,
                            })
                        }
                        idPrefix="content-type"
                    />

                    <div className="space-y-1">
                        <Label htmlFor="content-type-slug">
                            Slug (opsional — otomatis dari nama)
                        </Label>
                        <Input
                            id="content-type-slug"
                            value={form.data.slug}
                            onChange={(e) =>
                                form.setData('slug', e.target.value)
                            }
                            placeholder="slug-jenis-konten"
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="content-type-icon">
                            Ikon (opsional)
                        </Label>
                        <Input
                            id="content-type-icon"
                            value={form.data.icon}
                            onChange={(e) =>
                                form.setData('icon', e.target.value)
                            }
                            placeholder="nama-ikon"
                        />
                        <InputError message={form.errors.icon} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="content-type-writing-style">
                            Gaya bahasa
                        </Label>
                        <Select
                            value={
                                form.data.writing_style_id !== null
                                    ? String(form.data.writing_style_id)
                                    : 'none'
                            }
                            onValueChange={(value) =>
                                form.setData(
                                    'writing_style_id',
                                    value === 'none' ? null : Number(value),
                                )
                            }
                        >
                            <SelectTrigger
                                id="content-type-writing-style"
                                className="w-full"
                            >
                                <SelectValue placeholder="Tanpa gaya bahasa" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    Tanpa gaya bahasa
                                </SelectItem>
                                {writingStyles.map((style) => (
                                    <SelectItem
                                        key={style.id}
                                        value={String(style.id)}
                                    >
                                        {style.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.writing_style_id} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                            <Label htmlFor="content-type-archive-template">
                                Template arsip
                            </Label>
                            <Input
                                id="content-type-archive-template"
                                value={form.data.archive_template_key}
                                onChange={(e) =>
                                    form.setData(
                                        'archive_template_key',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={form.errors.archive_template_key}
                            />
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="content-type-single-template">
                                Template detail
                            </Label>
                            <Input
                                id="content-type-single-template"
                                value={form.data.single_template_key}
                                onChange={(e) =>
                                    form.setData(
                                        'single_template_key',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={form.errors.single_template_key}
                            />
                        </div>
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="content-type-sort-order">Urutan</Label>
                        <Input
                            id="content-type-sort-order"
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

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="content-type-is-active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                        />
                        <Label htmlFor="content-type-is-active">Aktif</Label>
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={contentTypesRoutes.index.url()}>Batal</a>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

ContentTypeForm.layout = {
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
