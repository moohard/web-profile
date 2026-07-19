type HtmlWidgetProps = {
    title?: string | null;
    content?: string | null;
};

/**
 * Widget HTML generik (konten sudah disanitasi di backend).
 */
export function HtmlWidget({ title, content }: HtmlWidgetProps) {
    return (
        <div className="rounded border p-4">
            {title && <h3 className="mb-2 font-semibold">{title}</h3>}
            {content && <div dangerouslySetInnerHTML={{ __html: content }} />}
        </div>
    );
}
