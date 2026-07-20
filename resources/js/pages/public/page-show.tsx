import type { SeoProps } from '@/components/seo/meta-head';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';

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
};

export default function PageShow(props: PageShowProps) {
    const { page, seo, ...layoutProps } = props;

    // Hindari dua <h1>: bila hero aktif dan punya heading, hero yang menyediakan
    // <h1> utama, jadi judul halaman cukup dirender sebagai teks bagi pembaca layar.
    const heroProvidesHeading = Boolean(
        layoutProps.region?.hero?.enabled && layoutProps.region.hero.heading,
    );

    return (
        <PublicLayout {...layoutProps} {...seo}>
            {heroProvidesHeading ? (
                <p className="sr-only">{page.title}</p>
            ) : (
                <h1>{page.title}</h1>
            )}
            <div
                dangerouslySetInnerHTML={{
                    __html: page.content?.html ?? '',
                }}
            />
        </PublicLayout>
    );
}
