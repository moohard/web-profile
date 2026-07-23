import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';

type FeaturedImage = {
    src: string;
    srcset: string;
    alt: string;
};

type PostProp = {
    id: number;
    slug: string;
    title: string;
    body: string | null;
    published_at: string | null;
    featured: FeaturedImage | null;
    category: { id: number; name: string } | null;
    tags: { id: number; name: string }[];
};

type SeoProp = {
    title: string;
    description?: string;
    canonical?: string;
    hreflang?: Record<string, string>;
    ogTitle?: string;
    ogDescription?: string;
    ogImage?: string;
    ogType?: string;
};

type PostShowProps = PublicLayoutSharedProps & {
    post: PostProp;
    contentType: { slug: string; name: string };
    seo: SeoProp;
    jsonLd?: Record<string, unknown>;
};

function formatDate(value: string | null): string | null {
    if (!value) {
        return null;
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function PostShow(props: PostShowProps) {
    const { post, contentType, seo, jsonLd, ...layoutProps } = props;

    return (
        <PublicLayout {...layoutProps} {...seo} jsonLd={jsonLd}>
            <article className="mx-auto max-w-4xl space-y-8 py-8">
                <header className="space-y-5">
                    <div className="flex flex-wrap items-center gap-3 text-sm">
                        <span className="font-semibold tracking-[0.14em] text-primary uppercase">
                            {contentType.name}
                        </span>
                        {post.category && (
                            <>
                                <span
                                    className="text-muted-foreground"
                                    aria-hidden="true"
                                >
                                    /
                                </span>
                                <span className="text-muted-foreground">
                                    {post.category.name}
                                </span>
                            </>
                        )}
                    </div>
                    <h1 className="text-4xl leading-tight font-bold tracking-tight sm:text-5xl">
                        {post.title}
                    </h1>
                    {formatDate(post.published_at) && (
                        <time
                            dateTime={post.published_at ?? ''}
                            className="block text-sm text-muted-foreground"
                        >
                            Dipublikasikan {formatDate(post.published_at)}
                        </time>
                    )}
                </header>

                {post.featured && (
                    <figure className="overflow-hidden rounded-2xl border bg-muted">
                        <img
                            src={post.featured.src}
                            srcSet={post.featured.srcset || undefined}
                            sizes="(min-width: 1024px) 896px, 100vw"
                            alt={post.featured.alt}
                            className="aspect-[16/9] w-full object-cover"
                        />
                    </figure>
                )}

                <div
                    className="prose prose-neutral dark:prose-invert prose-headings:scroll-mt-24 prose-a:text-primary max-w-none"
                    dangerouslySetInnerHTML={{ __html: post.body ?? '' }}
                />

                {post.tags.length > 0 && (
                    <footer className="border-t pt-6">
                        <p className="mb-3 text-sm font-semibold">Tag</p>
                        <ul className="flex flex-wrap gap-2">
                            {post.tags.map((tag) => (
                                <li
                                    key={tag.id}
                                    className="rounded-full border bg-muted/50 px-3 py-1 text-sm"
                                >
                                    {tag.name}
                                </li>
                            ))}
                        </ul>
                    </footer>
                )}
            </article>
        </PublicLayout>
    );
}
