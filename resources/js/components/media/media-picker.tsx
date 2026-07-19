import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { index as mediaIndex } from '@/routes/admin/media';

type MediaItem = {
    id: number;
    file_name: string;
    url: string;
    thumb_url?: string;
};

/**
 * Modal pemilih media untuk editor / form fitur admin.
 * Memuat daftar media via partial visit ke halaman pustaka media.
 */
export function MediaPicker({
    onPick,
}: {
    onPick: (mediaId: number, url: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [media, setMedia] = useState<MediaItem[]>([]);
    const [loading, setLoading] = useState(false);

    function load() {
        setLoading(true);
        router.visit(mediaIndex.url(), {
            only: ['media'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const pageMedia = page.props.media as
                    { data?: MediaItem[] } | undefined;
                setMedia(pageMedia?.data ?? []);
            },
            onFinish: () => setLoading(false),
        });
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                setOpen(value);

                if (value) {
                    load();
                }
            }}
        >
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    Pilih media
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Pilih media</DialogTitle>
                </DialogHeader>
                {loading && media.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Memuat media…
                    </p>
                ) : media.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada media di pustaka.
                    </p>
                ) : (
                    <div className="grid max-h-[60vh] grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-4">
                        {media.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                className="rounded border p-1 transition hover:ring-2 hover:ring-primary"
                                onClick={() => {
                                    onPick(item.id, item.url);
                                    setOpen(false);
                                }}
                            >
                                <img
                                    src={item.thumb_url || item.url}
                                    alt={item.file_name}
                                    className="aspect-square w-full object-cover"
                                    loading="lazy"
                                />
                            </button>
                        ))}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
