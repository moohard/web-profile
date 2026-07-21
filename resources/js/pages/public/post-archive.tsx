import { Link } from '@inertiajs/react';
import { Pagination } from '@/components/public/pagination';
import type { PaginationLink } from '@/components/public/pagination';
import type { SeoProps } from '@/components/seo/meta-head';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';

type ContentTypeProp = {
    slug: string;
    name: string;
};

type PostItem = {
    id: number;
    title: string;
    excerpt: string;
    url: string;
    image_url: string | null;
    image_srcset: string | null;
    published_at: string | null;
};

/** Bentuk paginator Laravel apa adanya (flat, bukan dibungkus `meta`). */
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

/** Format tanggal Indonesia — konsisten dengan idiom `toLocaleString('id-ID')` di admin. */
function formatDate(value: string | null): string | null {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function PostArchive(props: PostArchiveProps) {
    const { contentType, posts, seo, ...layoutProps } = props;

    return (
        <PublicLayout {...layoutProps} {...seo}>
            <h1 className="text-2xl font-semibold">{contentType.name}</h1>

            {posts.data.length === 0 ? (
                <p className="mt-6 text-sm text-muted-foreground">
                    Belum ada konten.
                </p>
            ) : (
                <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {posts.data.map((post) => (
                        <Card key={post.id} className="overflow-hidden">
                            {post.image_url && (
                                <img
                                    src={post.image_url}
                                    srcSet={post.image_srcset ?? undefined}
                                    sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                                    alt={post.title}
                                    className="aspect-video w-full object-cover"
                                    loading="lazy"
                                />
                            )}
                            <CardHeader>
                                <CardTitle>
                                    <Link
                                        href={post.url}
                                        className="hover:underline"
                                    >
                                        {post.title}
                                    </Link>
                                </CardTitle>
                                {post.published_at && (
                                    <p className="text-xs text-muted-foreground">
                                        {formatDate(post.published_at)}
                                    </p>
                                )}
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {post.excerpt}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <div className="mt-8">
                <Pagination links={posts.links} />
            </div>
        </PublicLayout>
    );
}
