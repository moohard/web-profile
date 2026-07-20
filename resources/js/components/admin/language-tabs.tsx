import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type LanguageOption = {
    id: number;
    code: string;
    name: string;
};

type LanguageTabsProps = {
    languages: LanguageOption[];
    values?: Record<number, string>;
    errors?: Record<number, string | undefined>;
    onChange?: (languageId: number, value: string) => void;
    idPrefix: string;
    /** Nilai deskripsi opsional per bahasa (mis. untuk Jenis Konten). */
    descriptionValues?: Record<number, string>;
    descriptionErrors?: Record<number, string | undefined>;
    onDescriptionChange?: (languageId: number, value: string) => void;
    /**
     * Render-prop opsional untuk konten panel kustom per bahasa (mis. editor
     * Post dengan banyak field: judul, slug, body, status, SEO). Bila
     * disediakan, menggantikan seluruhnya konten bawaan (nama/deskripsi) —
     * hanya chrome tab (tombol + state aktif) yang tetap dipakai bersama.
     */
    renderPanel?: (lang: LanguageOption) => ReactNode;
};

/**
 * Tab per bahasa untuk input nama (+ deskripsi opsional) terjemahan
 * (Category/Tag/ContentType), atau — via `renderPanel` — konten kustom
 * apa pun per bahasa (mis. editor Post). Tab dikendalikan via useState
 * lokal — bukan Radix — sesuai konvensi ringan admin.
 */
export default function LanguageTabs({
    languages,
    values,
    errors,
    onChange,
    idPrefix,
    descriptionValues,
    descriptionErrors,
    onDescriptionChange,
    renderPanel,
}: LanguageTabsProps) {
    const withDescription = onDescriptionChange !== undefined;
    const [active, setActive] = useState<number>(languages[0]?.id ?? 0);

    if (languages.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Tidak ada bahasa aktif.
            </p>
        );
    }

    return (
        <div className="space-y-2">
            <div
                className="flex flex-wrap gap-1 border-b"
                role="tablist"
                aria-label="Bahasa"
            >
                {languages.map((lang) => (
                    <button
                        key={lang.id}
                        type="button"
                        role="tab"
                        aria-selected={active === lang.id}
                        onClick={() => setActive(lang.id)}
                        className={cn(
                            'rounded-t-md px-3 py-1.5 text-sm font-medium transition-colors',
                            active === lang.id
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {lang.name}
                        {errors?.[lang.id] && (
                            <span className="ml-1 text-destructive">•</span>
                        )}
                    </button>
                ))}
            </div>

            {languages.map((lang) => (
                <div
                    key={lang.id}
                    role="tabpanel"
                    hidden={active !== lang.id}
                    className="space-y-1"
                >
                    {renderPanel ? (
                        renderPanel(lang)
                    ) : (
                        <>
                            <Label htmlFor={`${idPrefix}-name-${lang.id}`}>
                                Nama ({lang.code})
                            </Label>
                            <Input
                                id={`${idPrefix}-name-${lang.id}`}
                                value={values?.[lang.id] ?? ''}
                                onChange={(e) =>
                                    onChange?.(lang.id, e.target.value)
                                }
                                placeholder={`Nama dalam ${lang.name}`}
                            />
                            <InputError message={errors?.[lang.id]} />

                            {withDescription && (
                                <>
                                    <Label
                                        htmlFor={`${idPrefix}-description-${lang.id}`}
                                    >
                                        Deskripsi ({lang.code})
                                    </Label>
                                    <textarea
                                        id={`${idPrefix}-description-${lang.id}`}
                                        value={
                                            descriptionValues?.[lang.id] ?? ''
                                        }
                                        onChange={(e) =>
                                            onDescriptionChange?.(
                                                lang.id,
                                                e.target.value,
                                            )
                                        }
                                        rows={3}
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        placeholder={`Deskripsi dalam ${lang.name}`}
                                    />
                                    <InputError
                                        message={descriptionErrors?.[lang.id]}
                                    />
                                </>
                            )}
                        </>
                    )}
                </div>
            ))}
        </div>
    );
}
