import { HtmlWidget } from './widgets/html-widget';

export type WidgetItem = {
    type: string;
    config?: Record<string, unknown> | null;
    title?: string | null;
    content?: string | null;
};

/**
 * Dispatch widget ke komponen React berdasarkan type.
 */
export function WidgetRenderer({ widget }: { widget: WidgetItem }) {
    switch (widget.type) {
        case 'HtmlWidget':
            return (
                <HtmlWidget title={widget.title} content={widget.content} />
            );
        default:
            // Tipe widget lain ditambah di fase fitur berikutnya
            return null;
    }
}
