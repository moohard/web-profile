import { Head } from '@inertiajs/react';

type LatestPost = {
    id: number;
    title: string;
};

export default function PublicHome({
    latestPosts,
    locale,
}: {
    latestPosts: LatestPost[];
    locale: string;
}) {
    return (
        <>
            <Head title="Beranda">
                <link
                    rel="alternate"
                    hrefLang={locale}
                    href={
                        typeof window !== 'undefined'
                            ? window.location.href
                            : ''
                    }
                />
            </Head>
            <main className="prose p-8">
                <h1>Beranda</h1>
                <ul>
                    {latestPosts.map((p) => (
                        <li key={p.id}>{p.title}</li>
                    ))}
                </ul>
            </main>
        </>
    );
}
