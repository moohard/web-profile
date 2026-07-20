import { Link } from '@inertiajs/react';
import type { SeoProps } from '@/components/seo/meta-head';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';

type ContentTypeProp = {
    slug: string;
    name: string;
};

type PostItem = {
    id: number;
    title: string;
    url: string;
};

type PaginatedPosts = {
    data?: PostItem[];
};

type PostArchiveProps = PublicLayoutSharedProps & {
    contentType: ContentTypeProp;
    posts: PaginatedPosts;
    seo: SeoProps;
};

export default function PostArchive(props: PostArchiveProps) {
    const { contentType, posts, seo, ...layoutProps } = props;

    return (
        <PublicLayout {...layoutProps} {...seo}>
            <h1>{contentType.name}</h1>
            <ul>
                {posts.data?.map((p) => (
                    <li key={p.id}>
                        <Link href={p.url}>{p.title}</Link>
                    </li>
                ))}
            </ul>
        </PublicLayout>
    );
}
