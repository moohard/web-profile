import { Head, router } from '@inertiajs/react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';
import contactMessages, {
    index as contactMessagesIndex,
} from '@/routes/admin/contact-messages';

type ContactMessage = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    subject: string | null;
    message: string;
    status: string;
    created_at: string | null;
};

type Status = {
    value: string;
    label: string;
};

export default function ContactMessagesIndex({
    messages,
    statuses,
}: {
    messages: ContactMessage[];
    statuses: Status[];
}) {
    function updateStatus(message: ContactMessage, status: string) {
        router.put(
            contactMessages.update.url(message.id),
            { status },
            { preserveScroll: true },
        );
    }

    function deleteMessage(message: ContactMessage) {
        if (confirm(`Hapus pesan dari ${message.name}?`)) {
            router.delete(contactMessages.destroy.url(message.id), {
                preserveScroll: true,
            });
        }
    }

    const columns: DataTableColumn<ContactMessage>[] = [
        {
            key: 'sender',
            header: 'Pengirim',
            render: (message) => (
                <div>
                    <p className="font-medium">{message.name}</p>
                    <a
                        className="text-muted-foreground hover:underline"
                        href={`mailto:${message.email}`}
                    >
                        {message.email}
                    </a>
                </div>
            ),
        },
        {
            key: 'message',
            header: 'Pesan',
            render: (message) => (
                <div className="max-w-md">
                    {message.subject && (
                        <p className="font-medium">{message.subject}</p>
                    )}
                    <p className="line-clamp-2 text-muted-foreground">
                        {message.message}
                    </p>
                </div>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (message) => (
                <select
                    aria-label={`Status pesan ${message.name}`}
                    className="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                    value={message.status}
                    onChange={(event) =>
                        updateStatus(message, event.target.value)
                    }
                >
                    {statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </select>
            ),
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (message) => (
                <Button
                    type="button"
                    size="sm"
                    variant="destructive"
                    onClick={() => deleteMessage(message)}
                >
                    Hapus
                </Button>
            ),
        },
    ];

    return (
        <>
            <Head title="Pesan Kontak" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Pesan Kontak</h1>
                    <p className="text-sm text-muted-foreground">
                        Kelola pesan yang masuk dari situs publik.
                    </p>
                </div>
                <DataTable
                    columns={columns}
                    data={messages}
                    rowKey={(message) => message.id}
                    emptyMessage="Belum ada pesan kontak."
                />
            </div>
        </>
    );
}

ContactMessagesIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Pesan Kontak', href: contactMessagesIndex() },
    ],
};
