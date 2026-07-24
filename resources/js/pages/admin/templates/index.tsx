import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes/admin';
import { index as templatesIndex } from '@/routes/admin/templates';

type TemplateOption = {
    key: string;
    label: string;
};

export default function TemplatesIndex({
    templates,
}: {
    templates: TemplateOption[];
}) {
    return (
        <>
            <Head title="Template Registry" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Template Registry
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Daftar template read-only yang tersedia untuk halaman
                        (page), arsip, dan single. Registry ini digunakan untuk
                        validasi dan pilihan di editor.
                    </p>
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <table className="min-w-full divide-y divide-border">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left text-sm font-medium">
                                    Key
                                </th>
                                <th className="px-4 py-3 text-left text-sm font-medium">
                                    Label
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border bg-background">
                            {templates.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={2}
                                        className="px-4 py-6 text-center text-sm text-muted-foreground"
                                    >
                                        Tidak ada template.
                                    </td>
                                </tr>
                            )}
                            {templates.map((tpl) => (
                                <tr key={tpl.key}>
                                    <td className="px-4 py-3">
                                        <code className="rounded bg-muted px-1.5 py-0.5 text-sm">
                                            {tpl.key}
                                        </code>
                                    </td>
                                    <td className="px-4 py-3 text-sm">
                                        {tpl.label}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <p className="text-xs text-muted-foreground">
                    Read-only. Tidak ada operasi create/edit/delete. Keys ini
                    harus konsisten dengan yang dipakai PageController dan
                    ContentType.
                </p>
            </div>
        </>
    );
}

TemplatesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Template Registry',
            href: templatesIndex(),
        },
    ],
};
