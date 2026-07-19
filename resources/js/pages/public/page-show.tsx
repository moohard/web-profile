import { Head } from '@inertiajs/react';

type PageProp = {
    id: number;
    title: string;
    content?: {
        html?: string;
    } | null;
};

export default function PageShow({ page }: { page: PageProp }) {
    return (
        <>
            <Head title={page.title} />
            <main className="p-8">
                <h1>{page.title}</h1>
                <div
                    dangerouslySetInnerHTML={{
                        __html: page.content?.html ?? '',
                    }}
                />
            </main>
        </>
    );
}
