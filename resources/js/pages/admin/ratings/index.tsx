import { Head } from '@inertiajs/react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';

type Criterion = { id: number; name: string; average: number; total: number };
type Rating = {
    id: number;
    comment: string | null;
    created_at: string | null;
    scores: { criterion: string | null; score: number }[];
};

export default function RatingsIndex({
    totalRespondents,
    criteria,
    ratings,
}: {
    totalRespondents: number;
    criteria: Criterion[];
    ratings: Rating[];
}) {
    const columns: DataTableColumn<Rating>[] = [
        {
            key: 'date',
            header: 'Waktu',
            render: (rating) =>
                rating.created_at
                    ? new Date(rating.created_at).toLocaleString('id-ID')
                    : '-',
        },
        {
            key: 'comment',
            header: 'Komentar',
            render: (rating) => rating.comment ?? '-',
        },
        {
            key: 'scores',
            header: 'Skor',
            render: (rating) => (
                <ul className="space-y-1">
                    {rating.scores.map((score, index) => (
                        <li key={`${score.criterion}-${index}`}>
                            {score.criterion ?? 'Kriteria'}: {score.score}/5
                        </li>
                    ))}
                </ul>
            ),
        },
    ];

    return (
        <>
            <Head title="Penilaian" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Penilaian</h1>
                    <p className="text-muted-foreground">
                        {totalRespondents} penilai telah mengirimkan penilaian.
                    </p>
                </div>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {criteria.map((criterion) => (
                        <article
                            key={criterion.id}
                            className="rounded-lg border p-4"
                        >
                            <p className="text-sm text-muted-foreground">
                                {criterion.name}
                            </p>
                            <p className="mt-2 text-2xl font-semibold">
                                {criterion.average.toFixed(1)}{' '}
                                <span className="text-base font-normal text-muted-foreground">
                                    / 5
                                </span>
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {criterion.total} respons
                            </p>
                        </article>
                    ))}
                </div>
                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">
                        Submission terbaru
                    </h2>
                    <DataTable
                        columns={columns}
                        data={ratings}
                        rowKey={(rating) => rating.id}
                        emptyMessage="Belum ada penilaian."
                    />
                </section>
            </div>
        </>
    );
}
