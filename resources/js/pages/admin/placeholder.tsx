import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes/admin';

export default function AdminPlaceholder() {
    return (
        <>
            <Head title="Admin" />
            <div className="p-8">Area admin — bootstrap berhasil.</div>
        </>
    );
}

AdminPlaceholder.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
    ],
};
