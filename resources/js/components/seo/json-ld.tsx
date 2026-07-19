/**
 * Merender structured data JSON-LD di dalam script tag.
 */
export function JsonLd({
    data,
}: {
    data: Record<string, unknown> | Record<string, unknown>[];
}) {
    const json = JSON.stringify(data);

    return (
        <script
            type="application/ld+json"
            dangerouslySetInnerHTML={{ __html: json }}
        />
    );
}
