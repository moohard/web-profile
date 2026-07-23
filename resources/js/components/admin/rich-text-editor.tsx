import Image from '@tiptap/extension-image';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { useEffect, useRef } from 'react';
import { MediaPicker } from '@/components/media/media-picker';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type RichTextEditorProps = {
    id: string;
    value: string;
    onChange: (html: string) => void;
    ariaLabel: string;
};

function safeLink(rawUrl: string): string | null {
    const value = rawUrl.trim();

    if (value === '') {
        return '';
    }

    if (value.toLowerCase().startsWith('mailto:')) {
        return value;
    }

    try {
        const url = new URL(value);

        return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
    } catch {
        return null;
    }
}

export function RichTextEditor({
    id,
    value,
    onChange,
    ariaLabel,
}: RichTextEditorProps) {
    const onChangeRef = useRef(onChange);

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [2, 3],
                },
                link: {
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: 'https',
                    protocols: ['http', 'https', 'mailto'],
                },
            }),
            Image.configure({
                allowBase64: false,
            }),
        ],
        content: value,
        immediatelyRender: false,
        editorProps: {
            attributes: {
                id,
                'aria-label': ariaLabel,
                class: 'min-h-64 px-3 py-2 text-sm outline-none [&_blockquote]:border-l-4 [&_blockquote]:pl-4 [&_h2]:text-xl [&_h2]:font-semibold [&_h3]:text-lg [&_h3]:font-semibold [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6',
            },
        },
        onUpdate: ({ editor: currentEditor }) => {
            onChangeRef.current(currentEditor.getHTML());
        },
    });

    useEffect(() => {
        if (editor !== null && editor.getHTML() !== value) {
            editor.commands.setContent(value, { emitUpdate: false });
        }
    }, [editor, value]);

    if (editor === null) {
        return (
            <div
                className="min-h-64 animate-pulse rounded-md border bg-muted/40 motion-reduce:animate-none"
                aria-label={`Memuat ${ariaLabel}`}
            />
        );
    }

    function setLink() {
        if (editor === null) {
            return;
        }

        const currentHref = editor.getAttributes('link').href as
            string | undefined;
        const rawUrl = window.prompt(
            'Masukkan URL http(s) atau mailto:',
            currentHref ?? '',
        );

        if (rawUrl === null) {
            return;
        }

        const href = safeLink(rawUrl);

        if (href === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();

            return;
        }

        if (href === null) {
            window.alert('URL tidak valid. Gunakan http, https, atau mailto.');

            return;
        }

        editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
    }

    const toolbarButton = (isActive: boolean) =>
        cn(isActive && 'bg-accent text-accent-foreground');

    return (
        <div className="overflow-hidden rounded-md border border-input bg-background">
            <div
                className="flex flex-wrap items-center gap-1 border-b bg-muted/30 p-2"
                role="toolbar"
                aria-label={`Format ${ariaLabel}`}
            >
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(editor.isActive('paragraph'))}
                    onClick={() => editor.chain().focus().setParagraph().run()}
                    aria-label="Paragraf"
                    aria-pressed={editor.isActive('paragraph')}
                >
                    P
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(
                        editor.isActive('heading', { level: 2 }),
                    )}
                    onClick={() =>
                        editor.chain().focus().toggleHeading({ level: 2 }).run()
                    }
                    aria-label="Heading 2"
                    aria-pressed={editor.isActive('heading', { level: 2 })}
                >
                    H2
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(
                        editor.isActive('heading', { level: 3 }),
                    )}
                    onClick={() =>
                        editor.chain().focus().toggleHeading({ level: 3 }).run()
                    }
                    aria-label="Heading 3"
                    aria-pressed={editor.isActive('heading', { level: 3 })}
                >
                    H3
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(editor.isActive('bold'))}
                    onClick={() => editor.chain().focus().toggleBold().run()}
                    aria-label="Tebal"
                    aria-pressed={editor.isActive('bold')}
                >
                    B
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(editor.isActive('italic'))}
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                    aria-label="Miring"
                    aria-pressed={editor.isActive('italic')}
                >
                    I
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(editor.isActive('bulletList'))}
                    onClick={() =>
                        editor.chain().focus().toggleBulletList().run()
                    }
                    aria-pressed={editor.isActive('bulletList')}
                >
                    Daftar
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(editor.isActive('orderedList'))}
                    onClick={() =>
                        editor.chain().focus().toggleOrderedList().run()
                    }
                    aria-pressed={editor.isActive('orderedList')}
                >
                    Bernomor
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(editor.isActive('link'))}
                    onClick={setLink}
                    aria-pressed={editor.isActive('link')}
                >
                    Tautan
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={toolbarButton(editor.isActive('blockquote'))}
                    onClick={() =>
                        editor.chain().focus().toggleBlockquote().run()
                    }
                    aria-pressed={editor.isActive('blockquote')}
                >
                    Kutipan
                </Button>
                <MediaPicker
                    onPick={(_mediaId, url) => {
                        editor.chain().focus().setImage({ src: url }).run();
                    }}
                />
            </div>

            <EditorContent editor={editor} />
        </div>
    );
}
