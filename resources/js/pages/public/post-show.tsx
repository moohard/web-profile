import { Head } from '@inertiajs/react';

type ContentTypeProp = {
    slug: string;
    name: string;
};

type PostTranslationProp = {
    id: number;
    title: string;
    body?: string | null;
    slug: string;
};

export default function PostShow({
    post,
    contentType,
}: {
    post: PostTranslationProp;
    contentType: ContentTypeProp;
}) {
    return (
        <>
            <Head title={post.title} />
            <main className="prose p-8">
                <p className="text-sm text-muted-foreground">{contentType.name}</p>
                <h1>{post.title}</h1>
                {post.body ? (
                    <div dangerouslySetInnerHTML={{ __html: post.body }} />
                ) : null}
            </main>
        </>
    );
}
