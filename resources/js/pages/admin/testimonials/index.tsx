import { Head, router } from '@inertiajs/react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import { approve, destroy, reorder } from '@/routes/admin/testimonials';

type Testimonial = {
    id: number;
    author_name: string;
    author_title: string | null;
    content: string;
    status: 'Pending' | 'Approved';
    sort_order: number;
    photo_url: string | null;
};

export default function TestimonialsIndex({
    testimonials,
}: {
    testimonials: Testimonial[];
}) {
    function move(testimonial: Testimonial, direction: -1 | 1) {
        const index = testimonials.findIndex(
            (item) => item.id === testimonial.id,
        );
        const targetIndex = index + direction;

        if (targetIndex < 0 || targetIndex >= testimonials.length) {
            return;
        }

        const reordered = [...testimonials];
        [reordered[index], reordered[targetIndex]] = [
            reordered[targetIndex],
            reordered[index],
        ];
        router.put(
            reorder.url(),
            { testimonial_ids: reordered.map((item) => item.id) },
            { preserveScroll: true },
        );
    }

    const columns: DataTableColumn<Testimonial>[] = [
        {
            key: 'author',
            header: 'Pengirim',
            render: (testimonial) => (
                <div className="flex items-center gap-3">
                    {testimonial.photo_url && (
                        <img
                            src={testimonial.photo_url}
                            alt=""
                            className="h-9 w-9 rounded-full object-cover"
                        />
                    )}
                    <span>
                        <span className="block font-medium">
                            {testimonial.author_name}
                        </span>
                        {testimonial.author_title && (
                            <span className="text-muted-foreground">
                                {testimonial.author_title}
                            </span>
                        )}
                    </span>
                </div>
            ),
        },
        {
            key: 'content',
            header: 'Testimoni',
            render: (testimonial) => (
                <span className="line-clamp-2 max-w-md">
                    {testimonial.content}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (testimonial) =>
                testimonial.status === 'Approved' ? 'Disetujui' : 'Menunggu',
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (testimonial) => (
                <div className="flex justify-end gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => move(testimonial, -1)}
                    >
                        Naik
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => move(testimonial, 1)}
                    >
                        Turun
                    </Button>
                    {testimonial.status === 'Pending' && (
                        <Button
                            type="button"
                            size="sm"
                            onClick={() =>
                                router.patch(
                                    approve.url(testimonial.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            Setujui
                        </Button>
                    )}
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={() => {
                            if (
                                confirm(
                                    `Hapus testimoni dari ${testimonial.author_name}?`,
                                )
                            ) {
                                router.delete(destroy.url(testimonial.id), {
                                    preserveScroll: true,
                                });
                            }
                        }}
                    >
                        Tolak
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Testimoni" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Testimoni</h1>
                    <p className="text-muted-foreground">
                        Setujui atau hapus permanen submission publik.
                    </p>
                </div>
                <DataTable
                    columns={columns}
                    data={testimonials}
                    rowKey={(testimonial) => testimonial.id}
                    emptyMessage="Belum ada testimoni."
                />
            </div>
        </>
    );
}
