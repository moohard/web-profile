import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { SeoProps } from '@/components/seo/meta-head';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';

type ContentTypeProp = {
    slug: string;
    name: string;
};

type FeaturedImage = {
    src: string;
    srcset: string;
    alt: string;
};

type PostItem = {
    id: number;
    title: string;
    url: string;
    excerpt: string;
    featured: FeaturedImage | null;
    published_at: string | null;
    category: { id: number; name: string } | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedPosts = {
    data: PostItem[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
};

type PostArchiveProps = PublicLayoutSharedProps & {
    contentType: ContentTypeProp;
    posts: PaginatedPosts;
    seo: SeoProps;
};

function formatDate(value: string | null): string | null {
    if (!value) {
        return null;
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'long',
    }).format(new Date(value));
}

function paginationLabel(label: string): string {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace('Previous', 'Sebelumnya')
        .replace('Next', 'Berikutnya');
}

export default function PostArchive(props: PostArchiveProps) {
    const { contentType, posts, seo, ...layoutProps } = props;
    const [loading, setLoading] = useState(false);

    return (
        <PublicLayout {...layoutProps} {...seo}>
            <section
                className="space-y-8 py-8"
                aria-busy={loading}
                aria-live="polite"
            >
                <header className="max-w-3xl space-y-3">
                    <p className="text-sm font-semibold tracking-[0.18em] text-primary uppercase">
                        Arsip konten
                    </p>
                    <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
                        {contentType.name}
                    </h1>
                </header>

                {loading && (
                    <p className="rounded-xl border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                        Memuat konten…
                    </p>
                )}

                {posts.data.length === 0 ? (
                    <div className="rounded-2xl border border-dashed px-6 py-16 text-center">
                        <h2 className="text-lg font-semibold">
                            Belum ada konten
                        </h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Konten yang sudah dipublikasikan akan tampil di
                            sini.
                        </p>
                    </div>
                ) : (
                    <div
                        className={`grid gap-6 transition-opacity motion-reduce:transition-none sm:grid-cols-2 lg:grid-cols-3 ${
                            loading ? 'opacity-50' : 'opacity-100'
                        }`}
                    >
                        {posts.data.map((post) => (
                            <article
                                key={post.id}
                                className="group overflow-hidden rounded-2xl border bg-card shadow-sm transition hover:-translate-y-0.5 hover:shadow-md motion-reduce:transform-none motion-reduce:transition-none"
                            >
                                {post.featured ? (
                                    <img
                                        src={post.featured.src}
                                        srcSet={
                                            post.featured.srcset || undefined
                                        }
                                        sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                                        alt={post.featured.alt}
                                        className="aspect-[16/9] w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div
                                        className="aspect-[16/9] bg-gradient-to-br from-primary/15 via-muted to-muted/40"
                                        aria-hidden="true"
                                    />
                                )}
                                <div className="space-y-4 p-5">
                                    <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                        {post.category && (
                                            <span className="rounded-full bg-primary/10 px-2.5 py-1 font-medium text-primary">
                                                {post.category.name}
                                            </span>
                                        )}
                                        {formatDate(post.published_at) && (
                                            <time
                                                dateTime={
                                                    post.published_at ?? ''
                                                }
                                            >
                                                {formatDate(post.published_at)}
                                            </time>
                                        )}
                                    </div>
                                    <h2 className="text-xl leading-tight font-semibold">
                                        <Link
                                            href={post.url}
                                            className="outline-none after:absolute focus-visible:ring-2 focus-visible:ring-primary"
                                        >
                                            {post.title}
                                        </Link>
                                    </h2>
                                    {post.excerpt && (
                                        <p className="line-clamp-3 text-sm leading-6 text-muted-foreground">
                                            {post.excerpt}
                                        </p>
                                    )}
                                    <Link
                                        href={post.url}
                                        className="inline-flex text-sm font-semibold text-primary hover:underline"
                                    >
                                        Baca selengkapnya
                                        <span aria-hidden="true"> →</span>
                                    </Link>
                                </div>
                            </article>
                        ))}
                    </div>
                )}

                {posts.last_page > 1 && (
                    <nav
                        aria-label="Pagination konten"
                        className="flex flex-wrap justify-center gap-2"
                    >
                        {posts.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={`${link.label}-${index}`}
                                    href={link.url}
                                    preserveScroll
                                    onStart={() => setLoading(true)}
                                    onFinish={() => setLoading(false)}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                    className={`rounded-lg border px-3 py-2 text-sm font-medium ${
                                        link.active
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'bg-background hover:bg-muted'
                                    }`}
                                >
                                    {paginationLabel(link.label)}
                                </Link>
                            ) : (
                                <span
                                    key={`${link.label}-${index}`}
                                    className="cursor-not-allowed rounded-lg border px-3 py-2 text-sm text-muted-foreground opacity-50"
                                >
                                    {paginationLabel(link.label)}
                                </span>
                            ),
                        )}
                    </nav>
                )}
            </section>
        </PublicLayout>
    );
}
