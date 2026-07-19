import { Link, usePage } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useMemo } from 'react';
import {
    GROUP_LABELS,
    GROUP_ORDER,
    NAV_ITEMS,
} from '@/components/admin/sidebar-nav-config';
import type {
    NavGroup,
    NavItem as AdminNavItem,
} from '@/components/admin/sidebar-nav-config';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes/admin';
import type { NavItem } from '@/types';

type SharedContentType = {
    slug: string;
    name: string;
};

/**
 * Bangun daftar item sidebar admin: content types dinamis + NAV_ITEMS,
 * lalu filter berdasarkan permission user.
 */
function buildAdminNavItems(
    contentTypes: SharedContentType[],
    userPermissions: string[],
): AdminNavItem[] {
    const dynamicContentItems: AdminNavItem[] = contentTypes.map((ct) => ({
        label: ct.name,
        href: `/admin/posts?type=${ct.slug}`,
        icon: FileText,
        group: 'content' as const,
    }));

    return [...dynamicContentItems, ...NAV_ITEMS].filter(
        (item) => !item.permission || userPermissions.includes(item.permission),
    );
}

function toNavMainItem(item: AdminNavItem): NavItem {
    return {
        title: item.label,
        href: item.href,
        icon: item.icon,
    };
}

export function AppSidebar() {
    const { contentTypes, auth } = usePage().props;
    const userPermissions = auth?.user?.permissions as string[] | undefined;

    const groupedItems = useMemo(() => {
        // Normalisasi di dalam callback agar referensi array tidak berubah tiap render
        const types = (contentTypes as SharedContentType[] | undefined) ?? [];
        const permissions = userPermissions ?? [];
        const allItems = buildAdminNavItems(types, permissions);
        const byGroup = new Map<NavGroup, NavItem[]>();

        for (const item of allItems) {
            const list = byGroup.get(item.group) ?? [];
            list.push(toNavMainItem(item));
            byGroup.set(item.group, list);
        }

        return GROUP_ORDER.filter(
            (group) => (byGroup.get(group)?.length ?? 0) > 0,
        ).map((group) => ({
            group,
            label: GROUP_LABELS[group],
            items: byGroup.get(group) ?? [],
        }));
    }, [contentTypes, userPermissions]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {groupedItems.map(({ group, label, items }) => (
                    <NavMain key={group} items={items} label={label} />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
