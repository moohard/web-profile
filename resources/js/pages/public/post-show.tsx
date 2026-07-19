import PublicLayout, {
    type PublicLayoutSharedProps,
} from '@/layouts/public-layout';

type PostTranslationProp = {
    id: number;
    slug: string;
    title: string;
    body: string | null;
    meta_title: string | null;
    meta_description: string | null;
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
    seo: SeoProp;
    jsonLd?: Record<string, unknown>;
};

export default function PostShow(props: PostShowProps) {
    const { post, contentType, seo, jsonLd, ...layoutProps } = props;

    return (
        <PublicLayout {...layoutProps} {...seo} jsonLd={jsonLd}>
            <article className="prose">
                <p className="text-sm text-muted-foreground">
                    {contentType.name}
                </p>
                <h1>{post.title}</h1>
                <div
                    dangerouslySetInnerHTML={{ __html: post.body ?? '' }}
                />
            </article>
        </PublicLayout>
    );
}
