import { useState } from 'react';
import { readXsrfToken } from '@/components/admin/ai-suggest-button';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type PagePreviewPayload = {
    mode: 'Code' | 'Template';
    template_key: string;
    title: string;
    content: string;
};

type PagePreviewResponse = {
    preview?: PagePreviewPayload;
    message?: string;
};

export function PagePreviewDialog({
    endpoint,
    payload,
}: {
    endpoint: string;
    payload: () => PagePreviewPayload;
}) {
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [preview, setPreview] = useState<PagePreviewPayload | null>(null);

    async function requestPreview() {
        setOpen(true);
        setLoading(true);
        setError(null);
        setPreview(null);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': readXsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload()),
            });
            const data = (await response.json()) as PagePreviewResponse;

            if (!response.ok || data.preview === undefined) {
                setError(data.message ?? 'Draft tidak dapat dipratinjau.');

                return;
            }

            setPreview(data.preview);
        } catch {
            setError('Gagal menghubungi endpoint pratinjau.');
        } finally {
            setLoading(false);
        }
    }

    return (
        <>
            <Button type="button" variant="outline" onClick={requestPreview}>
                Pratinjau
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>
                            {preview?.title ?? 'Pratinjau halaman'}
                        </DialogTitle>
                        <DialogDescription>
                            Draft tersanitasi dan belum disimpan.
                        </DialogDescription>
                    </DialogHeader>

                    {loading && (
                        <div className="min-h-48 animate-pulse rounded-md bg-muted motion-reduce:animate-none" />
                    )}
                    {error && (
                        <p className="text-sm text-destructive">{error}</p>
                    )}
                    {!loading && !error && preview && (
                        <article
                            data-template={preview.template_key}
                            className="prose max-w-none"
                        >
                            <h1>{preview.title}</h1>
                            <div
                                dangerouslySetInnerHTML={{
                                    __html: preview.content,
                                }}
                            />
                        </article>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
