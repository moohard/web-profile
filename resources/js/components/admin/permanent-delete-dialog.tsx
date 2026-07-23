import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';

export function PermanentDeleteDialog({
    open,
    itemTitle,
    processing,
    onOpenChange,
    onConfirm,
}: {
    open: boolean;
    itemTitle: string;
    processing: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>Hapus permanen?</DialogTitle>
                <DialogDescription>
                    <strong>{itemTitle}</strong> beserta translation, relasi,
                    dan medianya akan dihapus permanen. Tindakan ini tidak dapat
                    dibatalkan.
                </DialogDescription>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={processing}
                        >
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={processing}
                        onClick={onConfirm}
                    >
                        {processing ? 'Menghapus…' : 'Hapus permanen'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
