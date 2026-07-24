import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { SyntheticEvent } from 'react';
import DataTable from '@/components/admin/data-table';
import type { DataTableColumn } from '@/components/admin/data-table';
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
import usersRoutes, { index as usersIndex } from '@/routes/admin/users';

type User = {
    id: number;
    name: string;
    email: string;
    role: string | null;
};

type UserFormData = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
};

function UserFormDialog({
    user,
    roles,
    onClose,
}: {
    user?: User;
    roles: string[];
    onClose: () => void;
}) {
    const isEditing = user !== undefined;
    const form = useForm<UserFormData>({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role: user?.role ?? roles[0] ?? '',
    });

    function submit(event: SyntheticEvent<HTMLFormElement>) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEditing) {
            form.put(usersRoutes.update.url(user.id), options);
        } else {
            form.post(usersRoutes.store.url(), options);
        }
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Ubah pengguna' : 'Tambah pengguna'}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1">
                        <Label htmlFor="user-name">Nama</Label>
                        <Input
                            id="user-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            autoComplete="name"
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="user-email">Email</Label>
                        <Input
                            id="user-email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            autoComplete="email"
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="user-role">Role</Label>
                        <Select
                            value={form.data.role}
                            onValueChange={(role) => form.setData('role', role)}
                        >
                            <SelectTrigger id="user-role" className="w-full">
                                <SelectValue placeholder="Pilih role" />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem key={role} value={role}>
                                        {role}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.role} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="user-password">
                            Password
                            {isEditing ? ' (kosongkan jika tidak diubah)' : ''}
                        </Label>
                        <Input
                            id="user-password"
                            type="password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            autoComplete="new-password"
                        />
                        <InputError message={form.errors.password} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="user-password-confirmation">
                            Konfirmasi password
                        </Label>
                        <Input
                            id="user-password-confirmation"
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(event) =>
                                form.setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                            autoComplete="new-password"
                        />
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

export default function UsersIndex({
    users,
    roles,
}: {
    users: User[];
    roles: string[];
}) {
    const [dialogFor, setDialogFor] = useState<number | 'new' | null>(null);

    function deleteUser(user: User) {
        if (!confirm(`Hapus pengguna "${user.name}"?`)) {
            return;
        }

        router.delete(usersRoutes.destroy.url(user.id), {
            preserveScroll: true,
        });
    }

    const columns: DataTableColumn<User>[] = [
        { key: 'name', header: 'Nama', render: (row) => row.name },
        { key: 'email', header: 'Email', render: (row) => row.email },
        { key: 'role', header: 'Role', render: (row) => row.role ?? '—' },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => setDialogFor(row.id)}
                    >
                        Ubah
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={() => deleteUser(row)}
                    >
                        Hapus
                    </Button>
                </div>
            ),
        },
    ];

    const editingUser =
        typeof dialogFor === 'number'
            ? users.find((user) => user.id === dialogFor)
            : undefined;

    return (
        <>
            <Head title="Pengguna" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">Pengguna</h1>
                    <Button type="button" onClick={() => setDialogFor('new')}>
                        Tambah pengguna
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={users}
                    rowKey={(row) => row.id}
                    emptyMessage="Belum ada pengguna. Tambahkan pengguna pertama."
                />
            </div>

            {dialogFor !== null && (
                <UserFormDialog
                    key={dialogFor}
                    user={editingUser}
                    roles={roles}
                    onClose={() => setDialogFor(null)}
                />
            )}
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Pengguna',
            href: usersIndex(),
        },
    ],
};
