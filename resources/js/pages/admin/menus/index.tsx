import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import LanguageTabs from '@/components/admin/language-tabs';
import type { LanguageOption } from '@/components/admin/language-tabs';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes/admin';
import menusRoutes, { index as menusIndex } from '@/routes/admin/menus';

type Translation = { language_id: number; label: string };

type MenuItem = {
    id: number;
    parent_id: number | null;
    link_type: 'Page' | 'ContentArchive' | 'ContentSingle' | 'Url';
    link_ref: string | null;
    url: string | null;
    sort_order: number;
    translations: Translation[];
    children: MenuItem[];
};

type Menu = {
    id: number;
    name: string;
    location: 'Header' | 'Footer';
    items: MenuItem[];
};

type MenuFormData = { name: string; location: 'Header' | 'Footer' };

type ItemFormData = {
    parent_id: number | null;
    link_type: MenuItem['link_type'];
    link_ref: string;
    url: string;
    sort_order: number;
    translations: Record<number, string>;
};

function labelFor(item: MenuItem, languages: LanguageOption[]): string {
    const translation = item.translations.find(
        (entry) => entry.language_id === languages[0]?.id,
    );

    return translation?.label ?? item.translations[0]?.label ?? '(tanpa label)';
}

function translationValues(
    languages: LanguageOption[],
    item?: MenuItem,
): Record<number, string> {
    return Object.fromEntries(
        languages.map((language) => [
            language.id,
            item?.translations.find(
                (translation) => translation.language_id === language.id,
            )?.label ?? '',
        ]),
    );
}

function MenuDialog({ menu, onClose }: { menu?: Menu; onClose: () => void }) {
    const form = useForm<MenuFormData>({
        name: menu?.name ?? '',
        location: menu?.location ?? 'Header',
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (menu) {
            form.put(menusRoutes.update.url(menu.id), options);
        } else {
            form.post(menusRoutes.store.url(), options);
        }
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {menu ? 'Ubah menu' : 'Tambah menu'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1">
                        <Label htmlFor="menu-name">Nama</Label>
                        <Input
                            id="menu-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="menu-location">Lokasi</Label>
                        <Select
                            value={form.data.location}
                            onValueChange={(value: MenuFormData['location']) =>
                                form.setData('location', value)
                            }
                        >
                            <SelectTrigger
                                id="menu-location"
                                className="w-full"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Header">Header</SelectItem>
                                <SelectItem value="Footer">Footer</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.location} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ItemDialog({
    menu,
    parent,
    languages,
    onClose,
}: {
    menu: Menu;
    parent?: MenuItem;
    languages: LanguageOption[];
    onClose: () => void;
}) {
    const form = useForm<ItemFormData>({
        parent_id: parent?.id ?? null,
        link_type: 'Url',
        link_ref: '',
        url: '',
        sort_order: parent ? parent.children.length + 1 : menu.items.length + 1,
        translations: translationValues(languages),
    });

    const translationErrors: Record<number, string | undefined> = {};
    languages.forEach((language, index) => {
        translationErrors[language.id] = (
            form.errors as Record<string, string>
        )[`translations.${index}.label`];
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            link_ref: data.link_type === 'Url' ? null : data.link_ref || null,
            url: data.link_type === 'Url' ? data.url || null : null,
            translations: languages.map((language) => ({
                language_id: language.id,
                label: data.translations[language.id] ?? '',
            })),
        }));
        form.post(menusRoutes.items.store.url(menu.id), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {parent
                            ? `Tambah sub-item untuk ${labelFor(parent, languages)}`
                            : 'Tambah item menu'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <LanguageTabs
                        languages={languages}
                        values={form.data.translations}
                        errors={translationErrors}
                        onChange={(languageId, value) =>
                            form.setData('translations', {
                                ...form.data.translations,
                                [languageId]: value,
                            })
                        }
                        idPrefix="menu-item"
                    />
                    <div className="space-y-1">
                        <Label htmlFor="link-type">Jenis tautan</Label>
                        <Select
                            value={form.data.link_type}
                            onValueChange={(value: MenuItem['link_type']) =>
                                form.setData('link_type', value)
                            }
                        >
                            <SelectTrigger id="link-type" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Page">Halaman</SelectItem>
                                <SelectItem value="ContentArchive">
                                    Arsip konten
                                </SelectItem>
                                <SelectItem value="ContentSingle">
                                    Konten tunggal
                                </SelectItem>
                                <SelectItem value="Url">URL</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    {form.data.link_type === 'Url' ? (
                        <div className="space-y-1">
                            <Label htmlFor="item-url">URL</Label>
                            <Input
                                id="item-url"
                                value={form.data.url}
                                onChange={(event) =>
                                    form.setData('url', event.target.value)
                                }
                                placeholder="/tujuan"
                            />
                            <InputError message={form.errors.url} />
                        </div>
                    ) : (
                        <div className="space-y-1">
                            <Label htmlFor="link-ref">ID target</Label>
                            <Input
                                id="link-ref"
                                value={form.data.link_ref}
                                onChange={(event) =>
                                    form.setData('link_ref', event.target.value)
                                }
                                placeholder="ID halaman atau konten"
                            />
                            <InputError message={form.errors.link_ref} />
                        </div>
                    )}
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Tambah'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function flattenedItems(items: MenuItem[]): MenuItem[] {
    return items.flatMap((item) => [item, ...item.children]);
}

export default function MenusIndex({
    menus,
    languages,
}: {
    menus: Menu[];
    languages: LanguageOption[];
}) {
    const [menuDialog, setMenuDialog] = useState<number | 'new' | null>(null);
    const [itemDialog, setItemDialog] = useState<{
        menu: Menu;
        parent?: MenuItem;
    } | null>(null);

    function saveOrder(menu: Menu, reorderedItems: MenuItem[]) {
        router.put(
            menusRoutes.items.sync.url(menu.id),
            {
                items: flattenedItems(reorderedItems).map((item) => ({
                    id: item.id,
                    parent_id: item.parent_id,
                    link_type: item.link_type,
                    link_ref: item.link_ref,
                    url: item.url,
                    sort_order: item.sort_order,
                    translations: item.translations,
                })),
            },
            { preserveScroll: true },
        );
    }

    function move(
        menu: Menu,
        parent: MenuItem | undefined,
        item: MenuItem,
        direction: -1 | 1,
    ) {
        const siblings = parent ? parent.children : menu.items;
        const currentIndex = siblings.findIndex(
            (candidate) => candidate.id === item.id,
        );
        const targetIndex = currentIndex + direction;

        if (targetIndex < 0 || targetIndex >= siblings.length) {
            return;
        }

        const updatedSiblings = siblings.map((candidate) => ({ ...candidate }));
        [updatedSiblings[currentIndex], updatedSiblings[targetIndex]] = [
            updatedSiblings[targetIndex],
            updatedSiblings[currentIndex],
        ];
        const reordered = updatedSiblings.map((candidate, index) => ({
            ...candidate,
            sort_order: index + 1,
        }));
        const items = parent
            ? menu.items.map((root) =>
                  root.id === parent.id
                      ? { ...root, children: reordered }
                      : root,
              )
            : reordered;

        saveOrder(menu, items);
    }

    function deleteMenu(menu: Menu) {
        if (confirm(`Hapus menu "${menu.name}"?`)) {
            router.delete(menusRoutes.destroy.url(menu.id), {
                preserveScroll: true,
            });
        }
    }

    const activeMenu =
        typeof menuDialog === 'number'
            ? menus.find((menu) => menu.id === menuDialog)
            : undefined;

    return (
        <>
            <Head title="Menu" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Menu</h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola navigasi Header dan Footer hingga dua
                            tingkat.
                        </p>
                    </div>
                    <Button type="button" onClick={() => setMenuDialog('new')}>
                        Tambah menu
                    </Button>
                </div>
                <div className="space-y-4">
                    {menus.map((menu) => (
                        <section
                            key={menu.id}
                            className="rounded-lg border bg-card p-4 shadow-sm"
                        >
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <h2 className="font-semibold">
                                        {menu.name}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {menu.location}
                                    </p>
                                </div>
                                <div className="flex flex-wrap justify-end gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => setItemDialog({ menu })}
                                    >
                                        Tambah item
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => setMenuDialog(menu.id)}
                                    >
                                        Ubah
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => deleteMenu(menu)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                            <ol className="mt-4 space-y-2">
                                {menu.items.map((item, index) => (
                                    <li
                                        key={item.id}
                                        className="rounded-md border p-3"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="font-medium">
                                                    {labelFor(item, languages)}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {item.link_type} ·{' '}
                                                    {item.link_type === 'Url'
                                                        ? item.url
                                                        : item.link_ref}
                                                </p>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={index === 0}
                                                    onClick={() =>
                                                        move(
                                                            menu,
                                                            undefined,
                                                            item,
                                                            -1,
                                                        )
                                                    }
                                                >
                                                    Naik
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={
                                                        index ===
                                                        menu.items.length - 1
                                                    }
                                                    onClick={() =>
                                                        move(
                                                            menu,
                                                            undefined,
                                                            item,
                                                            1,
                                                        )
                                                    }
                                                >
                                                    Turun
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setItemDialog({
                                                            menu,
                                                            parent: item,
                                                        })
                                                    }
                                                >
                                                    Tambah sub-item
                                                </Button>
                                            </div>
                                        </div>
                                        {item.children.length > 0 && (
                                            <ol className="mt-3 space-y-2 border-l pl-4">
                                                {item.children.map(
                                                    (child, childIndex) => (
                                                        <li
                                                            key={child.id}
                                                            className="flex items-center justify-between gap-3 rounded-md bg-muted/40 p-2"
                                                        >
                                                            <div>
                                                                <p className="font-medium">
                                                                    {labelFor(
                                                                        child,
                                                                        languages,
                                                                    )}
                                                                </p>
                                                                <p className="text-xs text-muted-foreground">
                                                                    {
                                                                        child.link_type
                                                                    }{' '}
                                                                    ·{' '}
                                                                    {child.link_type ===
                                                                    'Url'
                                                                        ? child.url
                                                                        : child.link_ref}
                                                                </p>
                                                            </div>
                                                            <div className="flex gap-2">
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    disabled={
                                                                        childIndex ===
                                                                        0
                                                                    }
                                                                    onClick={() =>
                                                                        move(
                                                                            menu,
                                                                            item,
                                                                            child,
                                                                            -1,
                                                                        )
                                                                    }
                                                                >
                                                                    Naik
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    disabled={
                                                                        childIndex ===
                                                                        item
                                                                            .children
                                                                            .length -
                                                                            1
                                                                    }
                                                                    onClick={() =>
                                                                        move(
                                                                            menu,
                                                                            item,
                                                                            child,
                                                                            1,
                                                                        )
                                                                    }
                                                                >
                                                                    Turun
                                                                </Button>
                                                            </div>
                                                        </li>
                                                    ),
                                                )}
                                            </ol>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        </section>
                    ))}
                </div>
            </div>
            {menuDialog !== null && (
                <MenuDialog
                    menu={activeMenu}
                    onClose={() => setMenuDialog(null)}
                />
            )}
            {itemDialog !== null && (
                <ItemDialog
                    menu={itemDialog.menu}
                    parent={itemDialog.parent}
                    languages={languages}
                    onClose={() => setItemDialog(null)}
                />
            )}
        </>
    );
}

MenusIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Menu', href: menusIndex() },
    ],
};
