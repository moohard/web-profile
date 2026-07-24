import { Head, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import LanguageTabs from '@/components/admin/language-tabs';
import type { LanguageOption } from '@/components/admin/language-tabs';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes/admin';
import { update } from '@/routes/admin/settings';

type SiteSettings = {
    site_name: string;
    logo_path: string | null;
    favicon_path: string | null;
    address: string | null;
    phone: string;
    email: string | null;
    social_links: Record<string, string>;
    maps_embed: string | null;
    contact_notification_email: string | null;
};

type SeoSettings = {
    default_meta_title: string | null;
    default_meta_description: string | null;
    og_default_image_path: string | null;
    default_og_type: 'website' | 'article' | 'profile';
};

type WhatsappSettings = {
    number: string;
    enabled: boolean;
    default_message: string;
};

type FooterText = {
    language_id: number;
    value: string;
};

type SiteSettingsForm = SiteSettings &
    SeoSettings & {
        footer_text: Record<number, string>;
        whatsapp_number: string;
        whatsapp_enabled: boolean;
        whatsapp_default_message: string;
    };

const socialPlatforms = ['instagram', 'facebook', 'x', 'youtube', 'linkedin'];

export default function SiteSettingsIndex({
    site,
    seo,
    footerText,
    whatsapp,
    languages,
}: {
    site: SiteSettings;
    seo: SeoSettings;
    footerText: FooterText[];
    whatsapp: WhatsappSettings;
    languages: LanguageOption[];
}) {
    const footerValues = Object.fromEntries(
        languages.map((language) => [
            language.id,
            footerText.find(
                (translation) => translation.language_id === language.id,
            )?.value ?? '',
        ]),
    );
    const form = useForm<SiteSettingsForm>({
        ...site,
        ...seo,
        social_links: { ...site.social_links },
        footer_text: footerValues,
        whatsapp_number: whatsapp.number,
        whatsapp_enabled: whatsapp.enabled,
        whatsapp_default_message: whatsapp.default_message,
    });

    const footerErrors: Record<number, string | undefined> = {};
    languages.forEach((language, index) => {
        footerErrors[language.id] = (form.errors as Record<string, string>)[
            `footer_text.${index}.value`
        ];
    });

    function submit(event: { preventDefault(): void }) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            social_links: Object.fromEntries(
                Object.entries(data.social_links).filter(([, value]) => value),
            ),
            footer_text: languages.map((language) => ({
                language_id: language.id,
                value: data.footer_text[language.id] ?? '',
            })),
        }));
        form.put(update.url(), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Pengaturan Situs" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Pengaturan Situs</h1>
                    <p className="text-sm text-muted-foreground">
                        Kelola identitas, SEO, kontak, footer, dan WhatsApp
                        situs.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Umum</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <Field
                                label="Nama situs"
                                id="site_name"
                                error={form.errors.site_name}
                            >
                                <Input
                                    id="site_name"
                                    value={form.data.site_name}
                                    onChange={(event) =>
                                        form.setData(
                                            'site_name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Path logo"
                                id="logo_path"
                                error={form.errors.logo_path}
                            >
                                <Input
                                    id="logo_path"
                                    value={form.data.logo_path ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            'logo_path',
                                            event.target.value || null,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Path favicon"
                                id="favicon_path"
                                error={form.errors.favicon_path}
                            >
                                <Input
                                    id="favicon_path"
                                    value={form.data.favicon_path ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            'favicon_path',
                                            event.target.value || null,
                                        )
                                    }
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>SEO</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Field
                                label="Judul meta default"
                                id="default_meta_title"
                                error={form.errors.default_meta_title}
                            >
                                <Input
                                    id="default_meta_title"
                                    value={form.data.default_meta_title ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            'default_meta_title',
                                            event.target.value || null,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Deskripsi meta default"
                                id="default_meta_description"
                                error={form.errors.default_meta_description}
                            >
                                <textarea
                                    id="default_meta_description"
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={
                                        form.data.default_meta_description ?? ''
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'default_meta_description',
                                            event.target.value || null,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Path gambar Open Graph"
                                id="og_default_image_path"
                                error={form.errors.og_default_image_path}
                            >
                                <Input
                                    id="og_default_image_path"
                                    value={
                                        form.data.og_default_image_path ?? ''
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'og_default_image_path',
                                            event.target.value || null,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Tipe Open Graph"
                                id="default_og_type"
                                error={form.errors.default_og_type}
                            >
                                <select
                                    id="default_og_type"
                                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                    value={form.data.default_og_type}
                                    onChange={(event) =>
                                        form.setData(
                                            'default_og_type',
                                            event.target
                                                .value as SeoSettings['default_og_type'],
                                        )
                                    }
                                >
                                    <option value="website">Website</option>
                                    <option value="article">Article</option>
                                    <option value="profile">Profile</option>
                                </select>
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Kontak &amp; Sosial</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Field
                                label="Alamat"
                                id="address"
                                error={form.errors.address}
                            >
                                <textarea
                                    id="address"
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={form.data.address ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            'address',
                                            event.target.value || null,
                                        )
                                    }
                                />
                            </Field>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field
                                    label="Telepon"
                                    id="phone"
                                    error={form.errors.phone}
                                >
                                    <Input
                                        id="phone"
                                        value={form.data.phone}
                                        onChange={(event) =>
                                            form.setData(
                                                'phone',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Email publik"
                                    id="email"
                                    error={form.errors.email}
                                >
                                    <Input
                                        id="email"
                                        type="email"
                                        value={form.data.email ?? ''}
                                        onChange={(event) =>
                                            form.setData(
                                                'email',
                                                event.target.value || null,
                                            )
                                        }
                                    />
                                </Field>
                            </div>
                            <Field
                                label="Email notifikasi kontak"
                                id="contact_notification_email"
                                error={form.errors.contact_notification_email}
                            >
                                <Input
                                    id="contact_notification_email"
                                    type="email"
                                    value={
                                        form.data.contact_notification_email ??
                                        ''
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'contact_notification_email',
                                            event.target.value || null,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Embed Google Maps"
                                id="maps_embed"
                                error={form.errors.maps_embed}
                            >
                                <textarea
                                    id="maps_embed"
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={form.data.maps_embed ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            'maps_embed',
                                            event.target.value || null,
                                        )
                                    }
                                />
                            </Field>
                            <div className="grid gap-4 md:grid-cols-2">
                                {socialPlatforms.map((platform) => (
                                    <Field
                                        key={platform}
                                        label={`URL ${platform}`}
                                        id={`social-${platform}`}
                                        error={
                                            (
                                                form.errors as Record<
                                                    string,
                                                    string
                                                >
                                            )[`social_links.${platform}`]
                                        }
                                    >
                                        <Input
                                            id={`social-${platform}`}
                                            type="url"
                                            value={
                                                form.data.social_links[
                                                    platform
                                                ] ?? ''
                                            }
                                            onChange={(event) =>
                                                form.setData('social_links', {
                                                    ...form.data.social_links,
                                                    [platform]:
                                                        event.target.value,
                                                })
                                            }
                                        />
                                    </Field>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Footer</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <LanguageTabs
                                languages={languages}
                                values={form.data.footer_text}
                                errors={footerErrors}
                                onChange={(languageId, value) =>
                                    form.setData('footer_text', {
                                        ...form.data.footer_text,
                                        [languageId]: value,
                                    })
                                }
                                idPrefix="footer"
                                renderPanel={(language) => (
                                    <div className="space-y-1">
                                        <Label
                                            htmlFor={`footer-${language.id}`}
                                        >
                                            Teks footer ({language.code})
                                        </Label>
                                        <textarea
                                            id={`footer-${language.id}`}
                                            rows={4}
                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            value={
                                                form.data.footer_text[
                                                    language.id
                                                ] ?? ''
                                            }
                                            onChange={(event) =>
                                                form.setData('footer_text', {
                                                    ...form.data.footer_text,
                                                    [language.id]:
                                                        event.target.value,
                                                })
                                            }
                                        />
                                        <InputError
                                            message={footerErrors[language.id]}
                                        />
                                    </div>
                                )}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>WhatsApp</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Field
                                label="Nomor WhatsApp"
                                id="whatsapp_number"
                                error={form.errors.whatsapp_number}
                            >
                                <Input
                                    id="whatsapp_number"
                                    value={form.data.whatsapp_number}
                                    onChange={(event) =>
                                        form.setData(
                                            'whatsapp_number',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="628123456789"
                                />
                            </Field>
                            <Field
                                label="Pesan default"
                                id="whatsapp_default_message"
                                error={form.errors.whatsapp_default_message}
                            >
                                <textarea
                                    id="whatsapp_default_message"
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={form.data.whatsapp_default_message}
                                    onChange={(event) =>
                                        form.setData(
                                            'whatsapp_default_message',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="whatsapp_enabled"
                                    checked={form.data.whatsapp_enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'whatsapp_enabled',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor="whatsapp_enabled">
                                    Aktifkan tombol WhatsApp
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Menyimpan…' : 'Simpan pengaturan'}
                    </Button>
                </form>
            </div>
        </>
    );
}

function Field({
    children,
    label,
    id,
    error,
}: {
    children: ReactNode;
    label: string;
    id: string;
    error?: string;
}) {
    return (
        <div className="space-y-1">
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

SiteSettingsIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Pengaturan Situs', href: '/admin/settings' },
    ],
};
