export type PostLanguageStatus = {
    code: string;
    label: string;
    status: string | null;
};

/**
 * Indikator status ringkas per-bahasa untuk daftar Post, mis. "ID ● · EN ○".
 * ● (terisi, hijau) = Published, ○ (kosong, abu-abu) = Draft atau belum ada
 * translation untuk bahasa tersebut sama sekali.
 */
export default function PostStatusIndicators({
    statuses,
}: {
    statuses: PostLanguageStatus[];
}) {
    return (
        <span className="inline-flex items-center gap-1.5 text-sm">
            {statuses.map((entry, index) => (
                <span
                    key={entry.code}
                    className="inline-flex items-center gap-1.5"
                >
                    {index > 0 && (
                        <span
                            aria-hidden="true"
                            className="text-muted-foreground"
                        >
                            ·
                        </span>
                    )}
                    <span
                        className="inline-flex items-center gap-1"
                        title={`${entry.label}: ${entry.status ?? 'Belum ada'}`}
                    >
                        <span className="font-medium text-muted-foreground">
                            {entry.label}
                        </span>
                        <span
                            aria-hidden="true"
                            className={
                                entry.status === 'Published'
                                    ? 'text-green-600 dark:text-green-500'
                                    : 'text-muted-foreground/50'
                            }
                        >
                            {entry.status === 'Published' ? '●' : '○'}
                        </span>
                        <span className="sr-only">
                            {entry.status ?? 'Belum ada'}
                        </span>
                    </span>
                </span>
            ))}
        </span>
    );
}
