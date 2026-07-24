import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/testimonial';

type TestimonialFormData = {
    author_name: string;
    author_title: string;
    content: string;
    photo: File | null;
};

export function TestimonialForm() {
    const form = useForm<TestimonialFormData>({
        author_name: '',
        author_title: '',
        content: '',
        photo: null,
    });

    function submit(event: React.SyntheticEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(store.url(), {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <form onSubmit={submit} className="space-y-4 rounded-lg border p-6">
            <div className="space-y-1">
                <Label htmlFor="testimonial-author-name">Nama</Label>
                <Input
                    id="testimonial-author-name"
                    value={form.data.author_name}
                    onChange={(event) =>
                        form.setData('author_name', event.target.value)
                    }
                    required
                />
                {form.errors.author_name && (
                    <p className="text-sm text-destructive">
                        {form.errors.author_name}
                    </p>
                )}
            </div>
            <div className="space-y-1">
                <Label htmlFor="testimonial-author-title">
                    Jabatan atau instansi
                </Label>
                <Input
                    id="testimonial-author-title"
                    value={form.data.author_title}
                    onChange={(event) =>
                        form.setData('author_title', event.target.value)
                    }
                />
            </div>
            <div className="space-y-1">
                <Label htmlFor="testimonial-content">Testimoni</Label>
                <textarea
                    id="testimonial-content"
                    value={form.data.content}
                    onChange={(event) =>
                        form.setData('content', event.target.value)
                    }
                    rows={5}
                    required
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
                {form.errors.content && (
                    <p className="text-sm text-destructive">
                        {form.errors.content}
                    </p>
                )}
            </div>
            <div className="space-y-1">
                <Label htmlFor="testimonial-photo">Foto (opsional)</Label>
                <Input
                    id="testimonial-photo"
                    type="file"
                    accept="image/*"
                    onChange={(event) =>
                        form.setData('photo', event.target.files?.[0] ?? null)
                    }
                />
                {form.errors.photo && (
                    <p className="text-sm text-destructive">
                        {form.errors.photo}
                    </p>
                )}
            </div>
            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Mengirim…' : 'Kirim testimoni'}
            </Button>
        </form>
    );
}
