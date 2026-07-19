import { ComingSoon } from '@/components/admin/coming-soon';
import { dashboard } from '@/routes/admin';

export default function AdminPlaceholder({ section }: { section: string }) {
    return <ComingSoon section={section} />;
}

AdminPlaceholder.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
    ],
};
