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
    ogType?: string;
};

type PostShowProps = PublicLayoutSharedProps & {
    post: PostTranslationProp;
    contentType: { slug: string; name: string };
    seo: SeoProp;
};

export default function PostShow(props: PostShowProps) {
    const { post, contentType, seo } = props;

    return (
        <PublicLayout
            {...props}
            title={seo?.title}
            description={seo?.description}
            canonical={seo?.canonical}
            hreflang={seo?.hreflang}
        >
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
