import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { Hero } from '@/components/public/hero';
import { WidgetRenderer } from '@/components/public/widget-renderer';
import type { WidgetItem } from '@/components/public/widget-renderer';
import { JsonLd } from '@/components/seo/json-ld';
import { MetaHead } from '@/components/seo/meta-head';

export type PublicMenuItem = {
    label: string;
    url?: string;
    children?: PublicMenuItem[];
};

/** Render item menu secara rekursif (mendukung sub-menu hierarkis). */
function MenuNodes({
    items,
    currentPath,
}: {
    items: PublicMenuItem[];
    currentPath: string;
}) {
    return (
        <>
            {items.map((item, i) => {
                const isActive =
                    item.url !== undefined && item.url === currentPath;

                return (
                    <li key={i}>
                        <a
                            href={item.url ?? '#'}
                            aria-current={isActive ? 'page' : undefined}
                        >
                            {item.label}
                        </a>
                        {item.children && item.children.length > 0 && (
                            <ul className="ml-4">
                                <MenuNodes
                                    items={item.children}
                                    currentPath={currentPath}
                                />
                            </ul>
                        )}
                    </li>
                );
            })}
        </>
    );
}

export type PublicRegion = {
    hero?: {
        enabled: boolean;
        image?: string;
        heading?: string;
        subheading?: string;
        ctaText?: string;
        ctaLink?: string;
    };
    sidebar?: { enabled: boolean; widgets: WidgetItem[] };
    widgets: {
        beforeContent: WidgetItem[];
        afterContent: WidgetItem[];
        sidebar: WidgetItem[];
        footer: WidgetItem[];
    };
};

/** Props layout yang di-share dari controller publik. */
export type PublicLayoutSharedProps = {
    locale: string;
    locales: { code: string; name: string }[];
    headerMenu?: PublicMenuItem[];
    footerMenu?: PublicMenuItem[];
    region?: PublicRegion;
};

type PublicLayoutProps = PublicLayoutSharedProps & {
    title?: string;
    description?: string;
    canonical?: string;
    hreflang?: Record<string, string>;
    ogTitle?: string;
    ogDescription?: string;
    ogImage?: string;
    ogType?: string;
    jsonLd?: Record<string, unknown> | Record<string, unknown>[];
    children: ReactNode;
};

/**
 * Shell layout publik: header, hero opsional, main+sidebar, footer + widget slots.
 * Meta SEO via MetaHead; structured data via JsonLd.
 */
export default function PublicLayout(props: PublicLayoutProps) {
    const { region, children, jsonLd } = props;
    const { url } = usePage();

    // Path tanpa prefix locale non-default (en)
    const pathWithoutLocale = url.replace(/^\/en(?=\/|$)/, '') || '/';
    // URL menu sudah locale-prefixed → bandingkan dengan path aktif penuh (tanpa query).
    const currentPath = url.split('?')[0];

    return (
        <>
            {props.title && (
                <MetaHead
                    title={props.title}
                    description={props.description}
                    canonical={props.canonical}
                    hreflang={props.hreflang}
                    ogTitle={props.ogTitle}
                    ogDescription={props.ogDescription}
                    ogImage={props.ogImage}
                    ogType={props.ogType}
                />
            )}

            {jsonLd && <JsonLd data={jsonLd} />}

            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:px-4 focus:py-2"
            >
                Lewati ke konten utama
            </a>

            <header className="border-b">
                <nav
                    aria-label="Navigasi utama"
                    className="mx-auto flex max-w-6xl items-center justify-between p-4"
                >
                    <a href="/" className="font-bold">
                        Papenajam
                    </a>
                    <ul className="flex gap-4">
                        <MenuNodes
                            items={props.headerMenu ?? []}
                            currentPath={currentPath}
                        />
                    </ul>
                    <LocaleSwitcher
                        currentLocale={props.locale}
                        locales={props.locales}
                        currentPath={pathWithoutLocale}
                    />
                </nav>
            </header>

            {region?.hero?.enabled && <Hero {...region.hero} />}

            <div
                className={`mx-auto max-w-6xl gap-6 p-4 ${region?.sidebar?.enabled ? 'md:grid md:grid-cols-[1fr_300px]' : ''}`}
            >
                <main id="main-content">
                    {(region?.widgets?.beforeContent ?? []).map((w, i) => (
                        <WidgetRenderer key={`bc-${i}`} widget={w} />
                    ))}
                    {children}
                    {(region?.widgets?.afterContent ?? []).map((w, i) => (
                        <WidgetRenderer key={`ac-${i}`} widget={w} />
                    ))}
                </main>

                {region?.sidebar?.enabled && (
                    <aside aria-label="Sidebar" className="space-y-4">
                        {(region?.widgets?.sidebar ?? []).map((w, i) => (
                            <WidgetRenderer key={`sb-${i}`} widget={w} />
                        ))}
                    </aside>
                )}
            </div>

            <footer className="border-t bg-muted/50">
                <div className="mx-auto max-w-6xl p-4">
                    <ul className="flex flex-wrap gap-4">
                        <MenuNodes
                            items={props.footerMenu ?? []}
                            currentPath={currentPath}
                        />
                    </ul>
                    {(region?.widgets?.footer ?? []).map((w, i) => (
                        <WidgetRenderer key={`ft-${i}`} widget={w} />
                    ))}
                </div>
            </footer>
        </>
    );
}
