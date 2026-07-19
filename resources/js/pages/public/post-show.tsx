import { MetaHead } from '@/components/seo/meta-head';

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

export default function PostShow({
    post,
    contentType,
    seo,
}: {
    post: PostTranslationProp;
    contentType: { slug: string; name: string };
    seo: SeoProp;
}) {
    return (
        <>
            <MetaHead {...seo} />
            <main className="prose mx-auto max-w-3xl p-8">
                <a href="/" className="text-sm text-blue-600 hover:underline">
                    ← Beranda
                </a>
                <p className="text-sm text-muted-foreground">
                    {contentType.name}
                </p>
                <h1>{post.title}</h1>
                <div
                    dangerouslySetInnerHTML={{ __html: post.body ?? '' }}
                />
            </main>
        </>
    );
}
