import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes/admin';

type DashboardStats = {
    posts: number;
    pages: number;
    media: number;
    contactNew: number;
};

type DraftPost = {
    id: number;
    title: string;
    updated_at: string;
};

type NewContactMessage = {
    id: number;
    name: string;
    subject: string | null;
    created_at: string;
};

export default function AdminDashboard({
    stats,
    draftPosts,
    newContactMessages,
}: {
    stats: DashboardStats;
    draftPosts: DraftPost[];
    newContactMessages: NewContactMessage[];
}) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">Dashboard</h1>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Konten</CardTitle>
                        </CardHeader>
                        <CardContent className="text-3xl font-bold">{stats.posts}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Halaman</CardTitle>
                        </CardHeader>
                        <CardContent className="text-3xl font-bold">{stats.pages}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Media</CardTitle>
                        </CardHeader>
                        <CardContent className="text-3xl font-bold">{stats.media}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Pesan baru</CardTitle>
                        </CardHeader>
                        <CardContent className="text-3xl font-bold">{stats.contactNew}</CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Draft terbaru</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {draftPosts.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Belum ada draft.</p>
                            ) : (
                                <ul className="space-y-1 text-sm">
                                    {draftPosts.map((post) => (
                                        <li key={post.id}>{post.title}</li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Pesan kontak baru</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {newContactMessages.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Belum ada pesan baru.</p>
                            ) : (
                                <ul className="space-y-1 text-sm">
                                    {newContactMessages.map((message) => (
                                        <li key={message.id}>
                                            {message.name}
                                            {message.subject ? ` — ${message.subject}` : ''}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
