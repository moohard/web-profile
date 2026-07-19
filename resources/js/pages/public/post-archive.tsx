import PublicLayout, {
    type PublicLayoutSharedProps,
} from '@/layouts/public-layout';

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

type PostArchiveProps = PublicLayoutSharedProps & {
    contentType: ContentTypeProp;
    posts: PaginatedPosts;
};

export default function PostArchive(props: PostArchiveProps) {
    const { contentType, posts } = props;

    return (
        <PublicLayout {...props} title={contentType.name}>
            <h1>{contentType.name}</h1>
            <ul>
                {posts.data?.map((p) => (
                    <li key={p.id}>{p.title}</li>
                ))}
            </ul>
        </PublicLayout>
    );
}
