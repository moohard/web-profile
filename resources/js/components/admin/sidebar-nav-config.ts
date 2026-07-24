import {
    Cpu,
    FileText,
    Files,
    FolderTree,
    GalleryVerticalEnd,
    Image,
    Languages,
    LayoutDashboard,
    LayoutTemplate,
    Mail,
    Menu as MenuIcon,
    PenTool,
    Quote,
    Settings as SettingsIcon,
    Star,
    Tag,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { dashboard } from '@/routes/admin';
import categories from '@/routes/admin/categories';
import contactMessages from '@/routes/admin/contact-messages';
import contentTypes from '@/routes/admin/content-types';
import galleries from '@/routes/admin/galleries';
import media from '@/routes/admin/media';
import menus from '@/routes/admin/menus';
import pages from '@/routes/admin/pages';
import ratingCriteria from '@/routes/admin/rating-criteria';
import ratings from '@/routes/admin/ratings';
import settings from '@/routes/admin/settings';
import tags from '@/routes/admin/tags';
import templates from '@/routes/admin/templates';
import testimonials from '@/routes/admin/testimonials';
import users from '@/routes/admin/users';
import widgets from '@/routes/admin/widgets';
import writingStyles from '@/routes/admin/writing-styles';

export type NavGroup =
    'dashboard' | 'content' | 'pages' | 'appearance' | 'interaction' | 'system';

export type NavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
    group: NavGroup;
    permission?: string;
    dynamicFrom?: 'contentTypes';
};

/** Urutan grup di sidebar admin. */
export const GROUP_ORDER: NavGroup[] = [
    'dashboard',
    'content',
    'pages',
    'appearance',
    'interaction',
    'system',
];

// Hanya Admin: appearance (Menu/Widget) + system (Users/Settings/AI/Languages/WritingStyles/RatingCriteria)
// Media tanpa permission agar Author/Editor tetap bisa mengakses pustaka media.
export const NAV_ITEMS: NavItem[] = [
    {
        label: 'Dashboard',
        href: dashboard.url(),
        icon: LayoutDashboard,
        group: 'dashboard',
    },

    // Konten (entri dinamis per content type di-prepend di app-sidebar)
    {
        label: 'Kategori',
        href: categories.index.url(),
        icon: FolderTree,
        group: 'content',
        permission: 'categories.viewAny',
    },
    {
        label: 'Tag',
        href: tags.index.url(),
        icon: Tag,
        group: 'content',
        permission: 'tags.viewAny',
    },
    {
        label: 'Galeri',
        href: galleries.index.url(),
        icon: GalleryVerticalEnd,
        group: 'content',
        permission: 'galleries.viewAny',
    },
    {
        label: 'Jenis konten',
        href: contentTypes.index.url(),
        icon: Files,
        group: 'content',
        permission: 'content-types.viewAny',
    },

    // Halaman
    {
        label: 'Halaman',
        href: pages.index.url(),
        icon: FileText,
        group: 'pages',
        permission: 'pages.viewAny',
    },

    // Tampilan — Admin only
    {
        label: 'Menu',
        href: menus.index.url(),
        icon: MenuIcon,
        group: 'appearance',
        permission: 'admin.access-appearance',
    },
    {
        label: 'Widget',
        href: widgets.index.url(),
        icon: LayoutTemplate,
        group: 'appearance',
        permission: 'admin.access-appearance',
    },

    // Interaksi
    {
        label: 'Pesan kontak',
        href: contactMessages.index.url(),
        icon: Mail,
        group: 'interaction',
        permission: 'contact-messages.viewAny',
    },
    {
        label: 'Testimoni',
        href: testimonials.index.url(),
        icon: Quote,
        group: 'interaction',
        permission: 'testimonials.viewAny',
    },
    {
        label: 'Penilaian',
        href: ratings.index.url(),
        icon: Star,
        group: 'interaction',
        permission: 'ratings.viewAny',
    },

    // Sistem — Admin only (kecuali Media)
    {
        label: 'Media',
        href: media.index.url(),
        icon: Image,
        group: 'system',
    },
    {
        label: 'Template',
        href: templates.index.url(),
        icon: LayoutTemplate,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Pengguna',
        href: users.index.url(),
        icon: Users,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Pengaturan',
        href: settings.index.url(),
        icon: SettingsIcon,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Konfigurasi AI',
        href: settings.ai.url(),
        icon: Cpu,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Bahasa',
        href: settings.languages.index.url(),
        icon: Languages,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Gaya bahasa',
        href: writingStyles.index.url(),
        icon: PenTool,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Kriteria penilaian',
        href: ratingCriteria.index.url(),
        icon: Star,
        group: 'system',
        permission: 'admin.access-system',
    },
];

export const GROUP_LABELS: Record<NavGroup, string> = {
    dashboard: 'Dashboard',
    content: 'Konten',
    pages: 'Halaman',
    appearance: 'Tampilan',
    interaction: 'Interaksi',
    system: 'Sistem',
};
