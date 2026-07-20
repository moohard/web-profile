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
    values: Record<number, string>;
    errors?: Record<number, string | undefined>;
    onChange: (languageId: number, value: string) => void;
    idPrefix: string;
};

/**
 * Tab per bahasa untuk input nama terjemahan (Category/Tag).
 * Tab dikendalikan via useState lokal — bukan Radix — sesuai konvensi ringan admin.
 */
export default function LanguageTabs({
    languages,
    values,
    errors,
    onChange,
    idPrefix,
}: LanguageTabsProps) {
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
                    <Label htmlFor={`${idPrefix}-name-${lang.id}`}>
                        Nama ({lang.code})
                    </Label>
                    <Input
                        id={`${idPrefix}-name-${lang.id}`}
                        value={values[lang.id] ?? ''}
                        onChange={(e) => onChange(lang.id, e.target.value)}
                        placeholder={`Nama dalam ${lang.name}`}
                    />
                    <InputError message={errors?.[lang.id]} />
                </div>
            ))}
        </div>
    );
}
