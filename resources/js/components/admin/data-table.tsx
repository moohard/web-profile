import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type DataTableColumn<T> = {
    key: string;
    header: string;
    render: (row: T) => ReactNode;
    className?: string;
};

type DataTableProps<T> = {
    columns: DataTableColumn<T>[];
    data: T[];
    rowKey: (row: T) => string | number;
    emptyMessage?: string;
};

/** Tabel ringan berbasis <table> + tailwind — tanpa dependensi tambahan. */
export default function DataTable<T>({
    columns,
    data,
    rowKey,
    emptyMessage = 'Belum ada data.',
}: DataTableProps<T>) {
    if (data.length === 0) {
        return (
            <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b bg-muted/50 text-left">
                        {columns.map((col) => (
                            <th
                                key={col.key}
                                className={cn(
                                    'px-4 py-2 font-medium text-muted-foreground',
                                    col.className,
                                )}
                            >
                                {col.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {data.map((row) => (
                        <tr
                            key={rowKey(row)}
                            className="border-b last:border-0 hover:bg-muted/30"
                        >
                            {columns.map((col) => (
                                <td
                                    key={col.key}
                                    className={cn('px-4 py-2', col.className)}
                                >
                                    {col.render(row)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
