import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Construction } from 'lucide-react';
import { dashboard } from '@/routes/admin';

/**
 * Placeholder UI untuk section admin yang belum diimplementasi.
 */
export function ComingSoon({ section }: { section: string }) {
    return (
        <>
            <Head title={section} />
            <div className="flex min-h-[60vh] flex-col items-center justify-center gap-4 p-8 text-center">
                <Construction className="h-16 w-16 text-muted-foreground" />
                <h1 className="text-2xl font-semibold">{section}</h1>
                <p className="text-muted-foreground">Bagian ini akan segera tersedia.</p>
                <Link
                    href={dashboard()}
                    className="mt-4 inline-flex items-center gap-2 rounded bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90"
                >
                    <ArrowLeft className="h-4 w-4" /> Kembali ke dashboard
                </Link>
            </div>
        </>
    );
}
