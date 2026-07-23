import type { SeoProps } from '@/components/seo/meta-head';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';
import { cn } from '@/lib/utils';

type PageProp = {
    id: number;
    title: string;
    content?: {
        html?: string;
    } | null;
};

type PageShowProps = PublicLayoutSharedProps & {
    page: PageProp;
    seo: SeoProps;
    templateKey: 'default' | 'full-width' | 'landing';
};

export default function PageShow(props: PageShowProps) {
    const { page, seo, templateKey, ...layoutProps } = props;

    // Hindari dua <h1>: bila hero aktif dan punya heading, hero yang menyediakan
    // <h1> utama, jadi judul halaman cukup dirender sebagai teks bagi pembaca layar.
    const heroProvidesHeading = Boolean(
        layoutProps.region?.hero?.enabled && layoutProps.region.hero.heading,
    );
    const templateClasses = {
        default: 'mx-auto max-w-3xl px-4 py-10 sm:px-6',
        'full-width': 'w-full px-4 py-10 sm:px-8',
        landing: 'mx-auto max-w-5xl px-4 py-14 sm:px-6',
    } as const;

    return (
        <PublicLayout {...layoutProps} {...seo}>
            <article
                data-template={templateKey}
                className={cn(templateClasses[templateKey])}
            >
                {heroProvidesHeading ? (
                    <p className="sr-only">{page.title}</p>
                ) : (
                    <h1 className="mb-8 text-4xl font-semibold tracking-tight">
                        {page.title}
                    </h1>
                )}
                <div
                    className="prose max-w-none"
                    dangerouslySetInnerHTML={{
                        __html: page.content?.html ?? '',
                    }}
                />
            </article>
        </PublicLayout>
    );
}
