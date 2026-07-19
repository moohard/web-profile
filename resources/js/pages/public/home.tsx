import PublicLayout, {
    type PublicLayoutSharedProps,
} from '@/layouts/public-layout';

type LatestPost = {
    id: number;
    title: string;
};

type HomeProps = PublicLayoutSharedProps & {
    latestPosts: LatestPost[];
};

export default function PublicHome(props: HomeProps) {
    const { latestPosts } = props;

    return (
        <PublicLayout {...props} title="Beranda">
            <h1>Beranda</h1>
            <ul>
                {latestPosts.map((p) => (
                    <li key={p.id}>{p.title}</li>
                ))}
            </ul>
        </PublicLayout>
    );
}
