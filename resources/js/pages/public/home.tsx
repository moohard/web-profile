import { Link } from '@inertiajs/react';
import type { SeoProps } from '@/components/seo/meta-head';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';

type LatestPost = {
    id: number;
    title: string;
    url: string;
};

type HomeProps = PublicLayoutSharedProps & {
    latestPosts: LatestPost[];
    seo: SeoProps;
    jsonLd?: Record<string, unknown>;
};

export default function PublicHome(props: HomeProps) {
    const { latestPosts, seo, jsonLd, ...layoutProps } = props;

    return (
        <PublicLayout {...layoutProps} {...seo} jsonLd={jsonLd}>
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
