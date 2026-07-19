import { Link } from '@inertiajs/react';

/**
 * Pengalih locale dasar (dirender di layout publik pada Fase 6).
 */
export function LocaleSwitcher({
    currentLocale,
    locales,
    currentPath,
}: {
    currentLocale: string;
    locales: { code: string; name: string }[];
    currentPath: string;
}) {
    return (
        <nav aria-label="Language" className="flex gap-2">
            {locales.map((l) => {
                const href =
                    l.code === 'id'
                        ? currentPath
                        : `/${l.code}${currentPath === '/' ? '' : currentPath}`;

                return (
                    <Link
                        key={l.code}
                        href={href}
                        aria-current={
                            l.code === currentLocale ? 'true' : undefined
                        }
                        className={`rounded px-2 py-1 text-sm ${
                            l.code === currentLocale
                                ? 'bg-blue-100 font-semibold'
                                : 'hover:bg-gray-100'
                        }`}
                    >
                        {l.name}
                    </Link>
                );
            })}
        </nav>
    );
}
