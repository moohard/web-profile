import { useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { picker as mediaPicker } from '@/routes/admin/media';

type MediaItem = {
    id: number;
    file_name: string;
    url: string;
    thumb_url?: string;
    alt?: string;
};

type MediaPickerResponse = {
    data: MediaItem[];
};

/**
 * Modal pemilih media untuk editor / form fitur admin.
 * Memuat JSON dengan HTTP mandiri agar state draft editor tetap utuh.
 */
export function MediaPicker({
    onPick,
}: {
    onPick: (mediaId: number, url: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [media, setMedia] = useState<MediaItem[]>([]);
    const [error, setError] = useState<string | null>(null);
    const { get, processing, cancel } = useHttp<
        Record<string, never>,
        MediaPickerResponse
    >({});

    function load() {
        setError(null);
        get(mediaPicker.url(), {
            onSuccess: (response) => {
                setMedia(response.data);
            },
            onHttpException: () => {
                setError('Media tidak dapat dimuat.');
            },
            onNetworkError: () => {
                setError('Koneksi gagal saat memuat media.');
            },
        }).catch(() => undefined);
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                setOpen(value);

                if (value) {
                    load();
                } else {
                    cancel();
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
                {processing && media.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Memuat media…
                    </p>
                ) : error !== null ? (
                    <div className="space-y-2">
                        <p role="alert" className="text-sm text-destructive">
                            {error}
                        </p>
                        <Button type="button" variant="outline" onClick={load}>
                            Coba lagi
                        </Button>
                    </div>
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
                                    alt={item.alt || item.file_name}
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
