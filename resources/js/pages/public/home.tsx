import { Link } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';

type LatestPost = {
    id: number;
    title: string;
    url: string;
};

type HomeProps = PublicLayoutSharedProps & {
    latestPosts: LatestPost[];
    jsonLd?: Record<string, unknown>;
};

export default function PublicHome(props: HomeProps) {
    const { latestPosts, jsonLd, ...layoutProps } = props;

    return (
        <PublicLayout {...layoutProps} title="Beranda" jsonLd={jsonLd}>
            <h1>Beranda</h1>
            <ul>
                {latestPosts.map((p) => (
                    <li key={p.id}>
                        <Link href={p.url}>{p.title}</Link>
                    </li>
                ))}
            </ul>
        </PublicLayout>
    );
}
