import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import type { Editor } from '@tiptap/react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
} from 'lucide-react';
import { useEffect } from 'react';
import { MediaPicker } from '@/components/media/media-picker';
import { Toggle } from '@/components/ui/toggle';

type RichTextEditorProps = {
    id?: string;
    value: string;
    onChange: (html: string) => void;
    ariaLabel?: string;
};

const HEADING_LEVELS = [1, 2, 3] as const;

/**
 * Editor rich-text (Tiptap) terkontrol — dipakai utk body Post & konten mode
 * Template Page. `value`/`onChange` berupa string HTML (bukan JSON Tiptap)
 * supaya selaras dengan field form lain; HTML disanitasi server via
 * `Sanitizer::cleanRichText` (profil `default`) sebagai boundary akhir.
 *
 * SSR-safe: `immediatelyRender: false` mencegah akses `document` saat render
 * di server (lihat resources/js/ssr.tsx) — editor baru terpasang setelah
 * hidrasi di client (guard `if (!editor)` di bawah).
 *
 * Ekstensi dibatasi agar selaras dengan allowlist purify profil `default`:
 * heading dibatasi h1-h3, `codeBlock` & `horizontalRule` dimatikan (kedua tag
 * tak ada di allowlist rich-text) — apa yang diketik = yang tersimpan setelah
 * sanitasi server.
 */
export function RichTextEditor({
    id,
    value,
    onChange,
    ariaLabel,
}: RichTextEditorProps) {
    const editor = useEditor({
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                link: false, // dikonfigurasi terpisah di bawah (butuh openOnClick: false)
                codeBlock: false,
                horizontalRule: false,
                heading: { levels: [...HEADING_LEVELS] },
            }),
            Link.configure({ openOnClick: false }),
            Image,
        ],
        content: value,
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
        editorProps: {
            attributes: {
                ...(id ? { id } : {}),
                ...(ariaLabel ? { 'aria-label': ariaLabel } : {}),
                class: 'prose min-h-[240px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none',
            },
        },
    });

    // Sinkron nilai dari luar (mis. hasil "Terjemahkan/Koreksi dengan AI" atau
    // ganti tab bahasa) tanpa memicu onUpdate berulang (emitUpdate: false) —
    // hanya set ulang bila memang berbeda supaya ketikan pengguna tak terganggu.
    useEffect(() => {
        if (editor && value !== editor.getHTML()) {
            editor.commands.setContent(value, { emitUpdate: false });
        }
    }, [value, editor]);

    if (!editor) {
        return (
            <div
                id={id}
                aria-label={ariaLabel}
                aria-busy="true"
                className="min-h-[240px] w-full animate-pulse rounded-md border border-input bg-muted"
            />
        );
    }

    return (
        <div className="space-y-2">
            <RichTextToolbar editor={editor} />
            <EditorContent editor={editor} />
        </div>
    );
}

function RichTextToolbar({ editor }: { editor: Editor }) {
    return (
        <div className="flex flex-wrap items-center gap-1 rounded-md border border-input bg-background p-1">
            {HEADING_LEVELS.map((level) => (
                <Toggle
                    key={level}
                    size="sm"
                    pressed={editor.isActive('heading', { level })}
                    onPressedChange={() =>
                        editor.chain().focus().toggleHeading({ level }).run()
                    }
                    aria-label={`Heading ${level}`}
                >
                    {`H${level}`}
                </Toggle>
            ))}
            <Toggle
                size="sm"
                pressed={editor.isActive('bold')}
                onPressedChange={() =>
                    editor.chain().focus().toggleBold().run()
                }
                aria-label="Tebal"
            >
                <Bold />
            </Toggle>
            <Toggle
                size="sm"
                pressed={editor.isActive('italic')}
                onPressedChange={() =>
                    editor.chain().focus().toggleItalic().run()
                }
                aria-label="Miring"
            >
                <Italic />
            </Toggle>
            <Toggle
                size="sm"
                pressed={editor.isActive('bulletList')}
                onPressedChange={() =>
                    editor.chain().focus().toggleBulletList().run()
                }
                aria-label="Daftar bullet"
            >
                <List />
            </Toggle>
            <Toggle
                size="sm"
                pressed={editor.isActive('orderedList')}
                onPressedChange={() =>
                    editor.chain().focus().toggleOrderedList().run()
                }
                aria-label="Daftar bernomor"
            >
                <ListOrdered />
            </Toggle>
            <Toggle
                size="sm"
                pressed={editor.isActive('blockquote')}
                onPressedChange={() =>
                    editor.chain().focus().toggleBlockquote().run()
                }
                aria-label="Kutipan"
            >
                <Quote />
            </Toggle>
            <Toggle
                size="sm"
                pressed={editor.isActive('link')}
                onPressedChange={() => {
                    if (editor.isActive('link')) {
                        editor.chain().focus().unsetLink().run();

                        return;
                    }

                    const url = window.prompt('URL tautan');

                    if (url) {
                        editor
                            .chain()
                            .focus()
                            .extendMarkRange('link')
                            .setLink({ href: url })
                            .run();
                    }
                }}
                aria-label="Tautan"
            >
                <LinkIcon />
            </Toggle>
            <MediaPicker
                onPick={(_mediaId, url) =>
                    editor.chain().focus().setImage({ src: url }).run()
                }
            />
        </div>
    );
}
