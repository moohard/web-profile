import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/rating';

type Criterion = {
    id: number;
    name: string;
    average: number;
    total: number;
};

type RatingSummaryProps = {
    totalRespondents: number;
    criteria: Criterion[];
};

type RatingForm = {
    comment: string;
    scores: { criterion_id: number; score: number }[];
};

function Stars({ value }: { value: number }) {
    return (
        <span
            aria-label={`${value.toFixed(1)} dari 5 bintang`}
            className="text-amber-500"
        >
            {'★'.repeat(Math.round(value))}
            {'☆'.repeat(5 - Math.round(value))}
        </span>
    );
}

export function RatingSummary({
    totalRespondents,
    criteria,
}: RatingSummaryProps) {
    const form = useForm<RatingForm>({
        comment: '',
        scores: criteria.map((criterion) => ({
            criterion_id: criterion.id,
            score: 5,
        })),
    });

    function updateScore(criterionId: number, score: number) {
        form.setData(
            'scores',
            form.data.scores.map((item) =>
                item.criterion_id === criterionId ? { ...item, score } : item,
            ),
        );
    }

    function submit() {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => form.reset('comment'),
        });
    }

    if (criteria.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="rating-heading"
            className="space-y-4 border-t border-border pt-6"
        >
            <div>
                <h2 id="rating-heading" className="font-semibold">
                    Penilaian layanan
                </h2>
                <p className="text-sm text-muted-foreground">
                    {totalRespondents} penilai telah memberikan masukan.
                </p>
            </div>
            <dl className="space-y-2 text-sm">
                {criteria.map((criterion) => (
                    <div
                        key={criterion.id}
                        className="flex items-center justify-between gap-4"
                    >
                        <dt>{criterion.name}</dt>
                        <dd className="shrink-0">
                            <Stars value={criterion.average} />{' '}
                            <span className="text-muted-foreground">
                                {criterion.average.toFixed(1)}
                            </span>
                        </dd>
                    </div>
                ))}
            </dl>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    submit();
                }}
                className="space-y-4"
                noValidate
            >
                <p className="text-sm font-medium">Beri penilaian Anda</p>
                {criteria.map((criterion) => {
                    const selectedScore =
                        form.data.scores.find(
                            (item) => item.criterion_id === criterion.id,
                        )?.score ?? 5;

                    return (
                        <fieldset
                            key={criterion.id}
                            className="flex items-center justify-between gap-3"
                        >
                            <legend className="text-sm">
                                {criterion.name}
                            </legend>
                            <div className="flex gap-1">
                                {[1, 2, 3, 4, 5].map((score) => (
                                    <label
                                        key={score}
                                        className="cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            name={`rating-${criterion.id}`}
                                            value={score}
                                            checked={selectedScore === score}
                                            onChange={() =>
                                                updateScore(criterion.id, score)
                                            }
                                            className="sr-only"
                                        />
                                        <span
                                            className={
                                                score <= selectedScore
                                                    ? 'text-amber-500'
                                                    : 'text-muted-foreground'
                                            }
                                            aria-hidden="true"
                                        >
                                            ★
                                        </span>
                                        <span className="sr-only">
                                            {score} bintang
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </fieldset>
                    );
                })}
                <div className="space-y-1">
                    <Label htmlFor="rating-comment">Komentar (opsional)</Label>
                    <textarea
                        id="rating-comment"
                        value={form.data.comment}
                        onChange={(event) =>
                            form.setData('comment', event.target.value)
                        }
                        className="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                    <InputError
                        message={
                            form.errors.comment ??
                            (form.errors as Record<string, string | undefined>)
                                .rating
                        }
                    />
                </div>
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Mengirim…' : 'Kirim penilaian'}
                </Button>
            </form>
        </section>
    );
}
