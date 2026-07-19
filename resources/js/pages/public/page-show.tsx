import PublicLayout, {
    type PublicLayoutSharedProps,
} from '@/layouts/public-layout';

type PageProp = {
    id: number;
    title: string;
    content?: {
        html?: string;
    } | null;
};

type PageShowProps = PublicLayoutSharedProps & {
    page: PageProp;
};

export default function PageShow(props: PageShowProps) {
    const { page } = props;

    return (
        <PublicLayout {...props} title={page.title}>
            <h1>{page.title}</h1>
            <div
                dangerouslySetInnerHTML={{
                    __html: page.content?.html ?? '',
                }}
            />
        </PublicLayout>
    );
}
