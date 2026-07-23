import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** Label "Previous"/"Next" bawaan paginator Laravel memuat entity HTML mentah
 * (&laquo;/&raquo;) — didekode di sini karena dirender sebagai teks React biasa. */
function decodeLabel(label: string): string {
    return label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');
}

/**
 * Navigasi halaman generik dari struktur `links` bawaan Laravel paginator
 * (`{ url, label, active }[]`). Tidak dirender bila hanya 1 halaman.
 */
export function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav
            aria-label="Navigasi halaman"
            className="flex flex-wrap justify-center gap-1"
        >
            {links.map((link, index) =>
                link.url === null ? (
                    <span
                        key={index}
                        className="rounded-md px-3 py-1.5 text-sm text-muted-foreground"
                    >
                        {decodeLabel(link.label)}
                    </span>
                ) : (
                    <Link
                        key={index}
                        href={link.url}
                        className={cn(
                            'rounded-md px-3 py-1.5 text-sm',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-muted',
                        )}
                    >
                        {decodeLabel(link.label)}
                    </Link>
                ),
            )}
        </nav>
    );
}
