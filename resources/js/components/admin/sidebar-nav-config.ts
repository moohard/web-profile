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
    type LucideIcon,
} from 'lucide-react';

export type NavGroup =
    | 'dashboard'
    | 'content'
    | 'pages'
    | 'appearance'
    | 'interaction'
    | 'system';

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
        href: '/admin',
        icon: LayoutDashboard,
        group: 'dashboard',
    },

    // Konten (entri dinamis per content type di-prepend di app-sidebar)
    {
        label: 'Kategori',
        href: '/admin/categories',
        icon: FolderTree,
        group: 'content',
        permission: 'content-types.viewAny',
    },
    {
        label: 'Tag',
        href: '/admin/tags',
        icon: Tag,
        group: 'content',
        permission: 'content-types.viewAny',
    },
    {
        label: 'Galeri',
        href: '/admin/galleries',
        icon: GalleryVerticalEnd,
        group: 'content',
        permission: 'galleries.viewAny',
    },
    {
        label: 'Jenis konten',
        href: '/admin/content-types',
        icon: Files,
        group: 'content',
        permission: 'content-types.viewAny',
    },

    // Halaman
    {
        label: 'Halaman',
        href: '/admin/pages',
        icon: FileText,
        group: 'pages',
        permission: 'pages.viewAny',
    },

    // Tampilan — Admin only
    {
        label: 'Menu',
        href: '/admin/menus',
        icon: MenuIcon,
        group: 'appearance',
        permission: 'admin.access-appearance',
    },
    {
        label: 'Widget',
        href: '/admin/widgets',
        icon: LayoutTemplate,
        group: 'appearance',
        permission: 'admin.access-appearance',
    },

    // Interaksi
    {
        label: 'Pesan kontak',
        href: '/admin/contact-messages',
        icon: Mail,
        group: 'interaction',
        permission: 'contact-messages.viewAny',
    },
    {
        label: 'Testimoni',
        href: '/admin/testimonials',
        icon: Quote,
        group: 'interaction',
        permission: 'testimonials.viewAny',
    },
    {
        label: 'Penilaian',
        href: '/admin/ratings',
        icon: Star,
        group: 'interaction',
        permission: 'ratings.viewAny',
    },

    // Sistem — Admin only (kecuali Media)
    {
        label: 'Media',
        href: '/admin/media',
        icon: Image,
        group: 'system',
    },
    {
        label: 'Pengguna',
        href: '/admin/users',
        icon: Users,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Pengaturan',
        href: '/admin/settings',
        icon: SettingsIcon,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Konfigurasi AI',
        href: '/admin/settings/ai',
        icon: Cpu,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Bahasa',
        href: '/admin/settings/languages',
        icon: Languages,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Gaya bahasa',
        href: '/admin/writing-styles',
        icon: PenTool,
        group: 'system',
        permission: 'admin.access-system',
    },
    {
        label: 'Kriteria penilaian',
        href: '/admin/rating-criteria',
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
