import { Head, Link } from '@inertiajs/react';
import {
    FileText,
    Images,
    LayoutTemplate,
    Mail,
    MessageSquareQuote,
    Star,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes/admin';
import { index as contactMessagesIndex } from '@/routes/admin/contact-messages';
import { index as mediaIndex } from '@/routes/admin/media';
import { index as pagesIndex } from '@/routes/admin/pages';
import { index as postsIndex } from '@/routes/admin/posts';
import { index as ratingsIndex } from '@/routes/admin/ratings';
import { index as testimonialsIndex } from '@/routes/admin/testimonials';

type DashboardStats = {
    posts: number;
    pages: number;
    media: number;
    contactNew: number;
    testimonialsPending: number;
    ratingAverage: number | null;
    ratingTotal: number;
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

type StatCard = {
    title: string;
    value: string;
    hint: string;
    href: string;
    icon: LucideIcon;
};

function buildStatCards(stats: DashboardStats): StatCard[] {
    return [
        {
            title: 'Konten',
            value: String(stats.posts),
            hint: 'Total artikel & berita',
            href: postsIndex().url,
            icon: FileText,
        },
        {
            title: 'Halaman',
            value: String(stats.pages),
            hint: 'Total halaman statis',
            href: pagesIndex().url,
            icon: LayoutTemplate,
        },
        {
            title: 'Media',
            value: String(stats.media),
            hint: 'Total berkas media',
            href: mediaIndex().url,
            icon: Images,
        },
        {
            title: 'Pesan baru',
            value: String(stats.contactNew),
            hint: 'Menunggu dibaca',
            href: contactMessagesIndex().url,
            icon: Mail,
        },
        {
            title: 'Testimoni pending',
            value: String(stats.testimonialsPending),
            hint: 'Menunggu moderasi',
            href: testimonialsIndex().url,
            icon: MessageSquareQuote,
        },
        {
            title: 'Rating rata-rata',
            value:
                stats.ratingAverage === null
                    ? '—'
                    : stats.ratingAverage.toFixed(1),
            hint: `${stats.ratingTotal} penilai`,
            href: ratingsIndex().url,
            icon: Star,
        },
    ];
}

export default function AdminDashboard({
    stats,
    draftPosts,
    newContactMessages,
}: {
    stats: DashboardStats;
    draftPosts: DraftPost[];
    newContactMessages: NewContactMessage[];
}) {
    const statCards = buildStatCards(stats);

    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">Dashboard</h1>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
                    {statCards.map((card) => (
                        <Link
                            key={card.title}
                            href={card.href}
                            className="block rounded-xl transition-shadow outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <Card className="h-full transition-colors hover:border-foreground/25">
                                <CardHeader className="flex-row items-center justify-between gap-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {card.title}
                                    </CardTitle>
                                    <card.icon
                                        className="size-4 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </CardHeader>
                                <CardContent>
                                    <p className="text-3xl font-bold">
                                        {card.value}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {card.hint}
                                    </p>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Draft terbaru</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {draftPosts.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada draft.
                                </p>
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
                                <p className="text-sm text-muted-foreground">
                                    Belum ada pesan baru.
                                </p>
                            ) : (
                                <ul className="space-y-1 text-sm">
                                    {newContactMessages.map((message) => (
                                        <li key={message.id}>
                                            {message.name}
                                            {message.subject
                                                ? ` — ${message.subject}`
                                                : ''}
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
