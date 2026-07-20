import { Head } from '@inertiajs/react';

export type SeoProps = {
    title: string;
    description?: string;
    canonical?: string;
    hreflang?: Record<string, string>; // locale → absolute URL
    ogTitle?: string;
    ogDescription?: string;
    ogImage?: string;
    ogType?: string;
};

/**
 * Head SEO dasar: title, description, canonical, hreflang, Open Graph.
 * Detail lanjutan (JSON-LD, og lengkap) di Fase 6.
 */
export function MetaHead(props: SeoProps) {
    // Map locale → URL sudah termasuk entri khusus `x-default` yang di-resolve
    // di server dari bahasa default (bukan sekadar urutan array).
    const hreflangEntries = props.hreflang
        ? Object.entries(props.hreflang)
        : [];

    return (
        <Head>
            <title>{props.title}</title>
            {props.description && (
                <meta
                    head-key="description"
                    name="description"
                    content={props.description}
                />
            )}
            {props.canonical && (
                <link
                    head-key="canonical"
                    rel="canonical"
                    href={props.canonical}
                />
            )}
            {hreflangEntries.map(([locale, url]) => (
                <link
                    key={locale}
                    head-key={`hreflang-${locale}`}
                    rel="alternate"
                    hrefLang={locale}
                    href={url}
                />
            ))}
            {props.ogTitle && (
                <meta
                    head-key="og:title"
                    property="og:title"
                    content={props.ogTitle}
                />
            )}
            {props.ogDescription && (
                <meta
                    head-key="og:description"
                    property="og:description"
                    content={props.ogDescription}
                />
            )}
            {props.ogImage && (
                <meta
                    head-key="og:image"
                    property="og:image"
                    content={props.ogImage}
                />
            )}
            <meta
                head-key="og:type"
                property="og:type"
                content={props.ogType ?? 'website'}
            />
        </Head>
    );
}
