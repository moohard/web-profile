/**
 * Merender structured data JSON-LD di dalam script tag.
 * Escape karakter HTML agar payload tidak memutus tag script (XSS).
 */
export function JsonLd({
    data,
}: {
    data: Record<string, unknown> | Record<string, unknown>[];
}) {
    const json = JSON.stringify(data)
        .replace(/</g, '\\u003c')
        .replace(/>/g, '\\u003e')
        .replace(/&/g, '\\u0026');

    return (
        <script
            type="application/ld+json"
            dangerouslySetInnerHTML={{ __html: json }}
        />
    );
}
