import { X } from 'lucide-react';
import { useState } from 'react';
import type { KeyboardEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type TagOption = {
    id: number;
    name: string;
};

type TagPickerProps = {
    /** Tag yang sudah ada (dari server) — dicentang seperti multi-select biasa. */
    options: TagOption[];
    /** Id tag existing yang dipilih. */
    selectedIds: number[];
    /** Nama tag baru yang diketik (create-on-type) — belum tersimpan, dibuat saat submit. */
    newNames: string[];
    onSelectedChange: (ids: number[]) => void;
    onNewNamesChange: (names: string[]) => void;
};

/**
 * Pemilih tag untuk editor Post: checkbox tag yang sudah ada + input
 * create-on-type untuk tag baru (Enter atau tombol "Tambah"). Nama yang
 * cocok (case-insensitive) dengan tag yang sudah ada otomatis dipilih —
 * bukan diajukan sebagai tag baru — supaya tidak terjadi duplikat. Server
 * (PostController::resolveTagIds) menjaga aturan yang sama sebagai
 * pertahanan kedua saat submit.
 */
export default function TagPicker({
    options,
    selectedIds,
    newNames,
    onSelectedChange,
    onNewNamesChange,
}: TagPickerProps) {
    const [draft, setDraft] = useState('');

    function toggleExisting(tagId: number, checked: boolean) {
        onSelectedChange(
            checked
                ? [...selectedIds, tagId]
                : selectedIds.filter((id) => id !== tagId),
        );
    }

    function addFromDraft() {
        const name = draft.trim();

        if (name === '') {
            return;
        }

        const normalized = name.toLowerCase();
        const existingMatch = options.find(
            (option) => option.name.toLowerCase() === normalized,
        );

        if (existingMatch) {
            if (!selectedIds.includes(existingMatch.id)) {
                onSelectedChange([...selectedIds, existingMatch.id]);
            }
        } else if (
            !newNames.some(
                (existingName) => existingName.toLowerCase() === normalized,
            )
        ) {
            onNewNamesChange([...newNames, name]);
        }

        setDraft('');
    }

    function removeNewName(name: string) {
        onNewNamesChange(newNames.filter((n) => n !== name));
    }

    function handleKeyDown(e: KeyboardEvent<HTMLInputElement>) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addFromDraft();
        }
    }

    return (
        <div className="space-y-2">
            <div className="space-y-1 rounded-md border p-3">
                {options.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Belum ada tag.
                    </p>
                )}
                {options.map((tag) => (
                    <div key={tag.id} className="flex items-center gap-2">
                        <Checkbox
                            id={`post-tag-${tag.id}`}
                            checked={selectedIds.includes(tag.id)}
                            onCheckedChange={(checked) =>
                                toggleExisting(tag.id, checked === true)
                            }
                        />
                        <Label
                            htmlFor={`post-tag-${tag.id}`}
                            className="font-normal"
                        >
                            {tag.name}
                        </Label>
                    </div>
                ))}
            </div>

            {newNames.length > 0 && (
                <div className="flex flex-wrap gap-1.5">
                    {newNames.map((name) => (
                        <Badge key={name} variant="secondary" className="gap-1">
                            {name}
                            <button
                                type="button"
                                onClick={() => removeNewName(name)}
                                aria-label={`Hapus tag baru ${name}`}
                                className="rounded-full hover:text-destructive"
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))}
                </div>
            )}

            <div className="flex gap-2">
                <Input
                    value={draft}
                    onChange={(e) => setDraft(e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder="Tag baru… (Enter untuk tambah)"
                />
                <Button type="button" variant="outline" onClick={addFromDraft}>
                    Tambah
                </Button>
            </div>
        </div>
    );
}
