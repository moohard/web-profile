import { Link } from '@inertiajs/react';

/**
 * Pengalih locale dasar (dirender di layout publik pada Fase 6).
 */
export function LocaleSwitcher({
    localeLinks,
}: {
    localeLinks: {
        code: string;
        name: string;
        url: string | null;
        isCurrent: boolean;
        isAvailable: boolean;
    }[];
}) {
    return (
        <nav aria-label="Language" className="flex gap-2">
            {localeLinks.map((localeLink) =>
                localeLink.isAvailable && localeLink.url ? (
                    <Link
                        key={localeLink.code}
                        href={localeLink.url}
                        aria-current={localeLink.isCurrent ? 'true' : undefined}
                        className={`rounded px-2 py-1 text-sm ${
                            localeLink.isCurrent
                                ? 'bg-blue-100 font-semibold'
                                : 'hover:bg-gray-100'
                        }`}
                    >
                        {localeLink.name}
                    </Link>
                ) : (
                    <span
                        key={localeLink.code}
                        aria-disabled="true"
                        title="Terjemahan belum tersedia"
                        className="cursor-not-allowed rounded px-2 py-1 text-sm opacity-40"
                    >
                        {localeLink.name}
                    </span>
                ),
            )}
        </nav>
    );
}
