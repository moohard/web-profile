import { Badge } from '@/components/ui/badge';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';

type PostTranslationProp = {
    id: number;
    slug: string;
    title: string;
    body: string | null;
    meta_title: string | null;
    meta_description: string | null;
};

type CategoryProp = {
    slug: string;
    name: string;
};

type TagProp = {
    slug: string;
    name: string;
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
    post: PostTranslationProp;
    contentType: { slug: string; name: string };
    category: CategoryProp | null;
    tags: TagProp[];
    seo: SeoProp;
    jsonLd?: Record<string, unknown>;
};

export default function PostShow(props: PostShowProps) {
    const { post, contentType, category, tags, seo, jsonLd, ...layoutProps } =
        props;

    return (
        <PublicLayout {...layoutProps} {...seo} jsonLd={jsonLd}>
            <article className="prose">
                <div className="not-prose flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    <span>{contentType.name}</span>
                    {category && (
                        <Badge variant="secondary">{category.name}</Badge>
                    )}
                </div>
                <h1>{post.title}</h1>
                <div dangerouslySetInnerHTML={{ __html: post.body ?? '' }} />

                {tags.length > 0 && (
                    <div className="not-prose mt-6 flex flex-wrap gap-2">
                        {tags.map((tag) => (
                            <Badge key={tag.slug} variant="outline">
                                {tag.name}
                            </Badge>
                        ))}
                    </div>
                )}
            </article>
        </PublicLayout>
    );
}
