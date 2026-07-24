import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes/admin';
import { index as contactMessagesIndex } from '@/routes/admin/contact-messages';

type ContactMessage = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    subject: string | null;
    message: string;
    status: string;
};

export default function ContactMessageShow({
    message,
}: {
    message: ContactMessage;
}) {
    return (
        <>
            <Head title={`Pesan dari ${message.name}`} />
            <div className="mx-auto max-w-3xl space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {message.subject ?? 'Pesan Kontak'}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Dari{' '}
                        <a
                            className="hover:underline"
                            href={`mailto:${message.email}`}
                        >
                            {message.name}
                        </a>{' '}
                        · {message.status}
                    </p>
                </div>
                <p className="rounded-lg border p-4 whitespace-pre-wrap">
                    {message.message}
                </p>
            </div>
        </>
    );
}

ContactMessageShow.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Pesan Kontak', href: contactMessagesIndex() },
    ],
};
