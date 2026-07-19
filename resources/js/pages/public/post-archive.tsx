import { Head } from '@inertiajs/react';

type ContentTypeProp = {
    slug: string;
    name: string;
};

type PostItem = {
    id: number;
    title: string;
};

type PaginatedPosts = {
    data?: PostItem[];
};

export default function PostArchive({
    contentType,
    posts,
}: {
    contentType: ContentTypeProp;
    posts: PaginatedPosts;
}) {
    return (
        <>
            <Head title={contentType.name} />
            <main className="p-8">
                <h1>{contentType.name}</h1>
                <ul>
                    {posts.data?.map((p) => (
                        <li key={p.id}>{p.title}</li>
                    ))}
                </ul>
            </main>
        </>
    );
}
