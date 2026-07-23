import { useState } from 'react';
import { Button } from '@/components/ui/button';

type AiSuggestResponse = {
    suggestion?: string;
    error?: string;
};

type AiSuggestButtonProps = {
    label: string;
    endpoint: string;
    payload: () => Record<string, unknown>;
    onAccept: (text: string) => void;
    /** Nonaktifkan tombol (mis. tidak ada teks sumber). */
    disabled?: boolean;
};

/** Baca nilai cookie XSRF-TOKEN (di-decode) untuk header `X-XSRF-TOKEN`. */
export function readXsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    if (!match) {
        return '';
    }

    return decodeURIComponent(match.slice('XSRF-TOKEN='.length));
}

/**
 * Tombol saran AI generik (terjemahkan / koreksi). Memanggil `endpoint`
 * dengan `payload()` via fetch JSON, lalu menampilkan hasil sebagai panel
 * saran non-destruktif dengan aksi "Terima" / "Batalkan". Tidak menulis apa
 * pun ke server — nilai hanya diterapkan ke form client-side via `onAccept`.
 */
export function AiSuggestButton({
    label,
    endpoint,
    payload,
    onAccept,
    disabled,
}: AiSuggestButtonProps) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [suggestion, setSuggestion] = useState<string | null>(null);

    async function requestSuggestion() {
        setLoading(true);
        setError(null);
        setSuggestion(null);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': readXsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload()),
            });

            const data = (await response.json()) as AiSuggestResponse;

            if (!response.ok) {
                setError(data.error ?? 'Gagal mendapatkan saran AI.');

                return;
            }

            if (!data.suggestion) {
                setError('AI tidak mengembalikan saran.');

                return;
            }

            setSuggestion(data.suggestion);
        } catch {
            setError('Gagal menghubungi layanan AI.');
        } finally {
            setLoading(false);
        }
    }

    function accept() {
        if (suggestion !== null) {
            onAccept(suggestion);
        }

        setSuggestion(null);
    }

    function cancel() {
        setSuggestion(null);
        setError(null);
    }

    return (
        <div className="space-y-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={disabled || loading}
                onClick={requestSuggestion}
            >
                {loading ? 'Memuat…' : label}
            </Button>

            {error && <p className="text-sm text-destructive">{error}</p>}

            {suggestion !== null && (
                <div className="space-y-2 rounded-md border bg-muted/40 p-3">
                    <p className="text-sm whitespace-pre-wrap">{suggestion}</p>
                    <div className="flex gap-2">
                        <Button type="button" size="sm" onClick={accept}>
                            Terima
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={cancel}
                        >
                            Batalkan
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

export default AiSuggestButton;
