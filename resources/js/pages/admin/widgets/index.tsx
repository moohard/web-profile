import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import widgetsRoutes from '@/routes/admin/widgets';

type Translation = {
    language_id: number;
    language_code?: string | null;
    title: string;
    content: string;
};

type Target = {
    target_type: string;
    target_ref: string;
};

type Placement = {
    id?: number;
    position: string;
    scope: string;
    sort_order: number;
    targets: Target[];
};

type WidgetItem = {
    id: number;
    type: 'HtmlWidget';
    is_active: boolean;
    translations: Translation[];
    placements: Placement[];
};

type Language = {
    id: number;
    code: string;
    name: string;
};

type Option = {
    value: string;
    label: string;
};

type WidgetForm = {
    type: 'HtmlWidget';
    is_active: boolean;
    translations: Translation[];
    placements: Placement[];
};

function emptyForm(
    languages: Language[],
    defaultPosition: string,
    defaultScope: string,
): WidgetForm {
    return {
        type: 'HtmlWidget',
        is_active: true,
        translations: languages.map((language) => ({
            language_id: language.id,
            title: '',
            content: '',
        })),
        placements: [
            {
                position: defaultPosition,
                scope: defaultScope,
                sort_order: 0,
                targets: [],
            },
        ],
    };
}

export default function WidgetsIndex({
    widgets,
    languages,
    positions,
    scopes,
    targetTypes,
}: {
    widgets: WidgetItem[];
    languages: Language[];
    positions: Option[];
    scopes: Option[];
    targetTypes: string[];
}) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const form = useForm<WidgetForm>(
        emptyForm(
            languages,
            positions[0]?.value ?? 'Sidebar',
            scopes[0]?.value ?? 'All',
        ),
    );

    function resetForm() {
        setEditingId(null);
        form.setData(
            emptyForm(
                languages,
                positions[0]?.value ?? 'Sidebar',
                scopes[0]?.value ?? 'All',
            ),
        );
        form.clearErrors();
    }

    function edit(widget: WidgetItem) {
        setEditingId(widget.id);
        form.setData({
            type: 'HtmlWidget',
            is_active: widget.is_active,
            translations: languages.map((language) => {
                const translation = widget.translations.find(
                    (item) => item.language_id === language.id,
                );

                return {
                    language_id: language.id,
                    title: translation?.title ?? '',
                    content: translation?.content ?? '',
                };
            }),
            placements: widget.placements.map(
                ({ position, scope, sort_order, targets }) => ({
                    position,
                    scope,
                    sort_order,
                    targets: targets.map((target) => ({ ...target })),
                }),
            ),
        });
        form.clearErrors();
    }

    function submit(event: { preventDefault(): void }) {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: resetForm };

        if (editingId !== null) {
            form.put(widgetsRoutes.update.url(editingId), options);

            return;
        }

        form.post(widgetsRoutes.store.url(), options);
    }

    function updateTranslation(
        index: number,
        field: 'title' | 'content',
        value: string,
    ) {
        form.setData(
            'translations',
            form.data.translations.map((translation, translationIndex) =>
                translationIndex === index
                    ? { ...translation, [field]: value }
                    : translation,
            ),
        );
    }

    function updatePlacement(
        index: number,
        field: 'position' | 'scope' | 'sort_order',
        value: string,
    ) {
        form.setData(
            'placements',
            form.data.placements.map((placement, placementIndex) =>
                placementIndex === index
                    ? {
                          ...placement,
                          [field]:
                              field === 'sort_order' ? Number(value) : value,
                      }
                    : placement,
            ),
        );
    }

    function addPlacement() {
        form.setData('placements', [
            ...form.data.placements,
            {
                position: positions[0]?.value ?? 'Sidebar',
                scope: scopes[0]?.value ?? 'All',
                sort_order: 0,
                targets: [],
            },
        ]);
    }

    function removePlacement(index: number) {
        form.setData(
            'placements',
            form.data.placements.filter(
                (_, placementIndex) => placementIndex !== index,
            ),
        );
    }

    function addTarget(placementIndex: number) {
        form.setData(
            'placements',
            form.data.placements.map((placement, index) =>
                index === placementIndex
                    ? {
                          ...placement,
                          targets: [
                              ...placement.targets,
                              {
                                  target_type: targetTypes[0] ?? 'Page',
                                  target_ref: '',
                              },
                          ],
                      }
                    : placement,
            ),
        );
    }

    function updateTarget(
        placementIndex: number,
        targetIndex: number,
        field: keyof Target,
        value: string,
    ) {
        form.setData(
            'placements',
            form.data.placements.map((placement, index) =>
                index === placementIndex
                    ? {
                          ...placement,
                          targets: placement.targets.map((target, index) =>
                              index === targetIndex
                                  ? { ...target, [field]: value }
                                  : target,
                          ),
                      }
                    : placement,
            ),
        );
    }

    function removeTarget(placementIndex: number, targetIndex: number) {
        form.setData(
            'placements',
            form.data.placements.map((placement, index) =>
                index === placementIndex
                    ? {
                          ...placement,
                          targets: placement.targets.filter(
                              (_, index) => index !== targetIndex,
                          ),
                      }
                    : placement,
            ),
        );
    }

    function destroy(widget: WidgetItem) {
        if (
            !window.confirm(
                'Hapus widget ini? Tindakan ini tidak dapat dibatalkan.',
            )
        ) {
            return;
        }

        router.delete(widgetsRoutes.destroy.url(widget.id), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Widget" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Widget</h1>
                    <p className="text-sm text-muted-foreground">
                        Kelola widget HTML dan penempatannya pada halaman
                        publik.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {editingId === null
                                ? 'Tambah widget'
                                : 'Ubah widget'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="flex items-center gap-3">
                                <input
                                    id="widget-active"
                                    type="checkbox"
                                    checked={form.data.is_active}
                                    onChange={(event) =>
                                        form.setData(
                                            'is_active',
                                            event.target.checked,
                                        )
                                    }
                                />
                                <Label htmlFor="widget-active">
                                    Aktifkan widget
                                </Label>
                            </div>

                            <div className="space-y-4">
                                <h2 className="font-medium">Terjemahan</h2>
                                {languages.map((language, index) => (
                                    <div
                                        key={language.id}
                                        className="grid gap-3 rounded-md border p-4"
                                    >
                                        <p className="text-sm font-medium">
                                            {language.name} ({language.code})
                                        </p>
                                        <div className="space-y-1">
                                            <Label
                                                htmlFor={`widget-title-${language.id}`}
                                            >
                                                Judul
                                            </Label>
                                            <Input
                                                id={`widget-title-${language.id}`}
                                                value={
                                                    form.data.translations[
                                                        index
                                                    ]?.title ?? ''
                                                }
                                                onChange={(event) =>
                                                    updateTranslation(
                                                        index,
                                                        'title',
                                                        event.target.value,
                                                    )
                                                }
                                                maxLength={255}
                                            />
                                            <InputError
                                                message={
                                                    form.errors[
                                                        `translations.${index}.title`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label
                                                htmlFor={`widget-content-${language.id}`}
                                            >
                                                Konten HTML
                                            </Label>
                                            <textarea
                                                id={`widget-content-${language.id}`}
                                                value={
                                                    form.data.translations[
                                                        index
                                                    ]?.content ?? ''
                                                }
                                                onChange={(event) =>
                                                    updateTranslation(
                                                        index,
                                                        'content',
                                                        event.target.value,
                                                    )
                                                }
                                                rows={5}
                                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            />
                                            <InputError
                                                message={
                                                    form.errors[
                                                        `translations.${index}.content`
                                                    ]
                                                }
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="space-y-4">
                                <div className="flex items-center justify-between gap-3">
                                    <h2 className="font-medium">Penempatan</h2>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addPlacement}
                                    >
                                        Tambah placement
                                    </Button>
                                </div>
                                {form.data.placements.map(
                                    (placement, placementIndex) => (
                                        <div
                                            key={placementIndex}
                                            className="space-y-4 rounded-md border p-4"
                                        >
                                            <div className="grid gap-3 md:grid-cols-3">
                                                <label className="space-y-1 text-sm">
                                                    <span>Posisi</span>
                                                    <select
                                                        value={
                                                            placement.position
                                                        }
                                                        onChange={(event) =>
                                                            updatePlacement(
                                                                placementIndex,
                                                                'position',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="w-full rounded-md border border-input bg-background px-3 py-2"
                                                    >
                                                        {positions.map(
                                                            (position) => (
                                                                <option
                                                                    key={
                                                                        position.value
                                                                    }
                                                                    value={
                                                                        position.value
                                                                    }
                                                                >
                                                                    {
                                                                        position.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                </label>
                                                <label className="space-y-1 text-sm">
                                                    <span>Scope</span>
                                                    <select
                                                        value={placement.scope}
                                                        onChange={(event) =>
                                                            updatePlacement(
                                                                placementIndex,
                                                                'scope',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="w-full rounded-md border border-input bg-background px-3 py-2"
                                                    >
                                                        {scopes.map((scope) => (
                                                            <option
                                                                key={
                                                                    scope.value
                                                                }
                                                                value={
                                                                    scope.value
                                                                }
                                                            >
                                                                {scope.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </label>
                                                <label className="space-y-1 text-sm">
                                                    <span>Urutan</span>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        value={
                                                            placement.sort_order
                                                        }
                                                        onChange={(event) =>
                                                            updatePlacement(
                                                                placementIndex,
                                                                'sort_order',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </label>
                                            </div>

                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between gap-3">
                                                    <p className="text-sm font-medium">
                                                        Target
                                                    </p>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            addTarget(
                                                                placementIndex,
                                                            )
                                                        }
                                                    >
                                                        Tambah target
                                                    </Button>
                                                </div>
                                                {placement.targets.map(
                                                    (target, targetIndex) => (
                                                        <div
                                                            key={targetIndex}
                                                            className="grid gap-2 md:grid-cols-[1fr_1fr_auto]"
                                                        >
                                                            <select
                                                                value={
                                                                    target.target_type
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateTarget(
                                                                        placementIndex,
                                                                        targetIndex,
                                                                        'target_type',
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                            >
                                                                {targetTypes.map(
                                                                    (
                                                                        targetType,
                                                                    ) => (
                                                                        <option
                                                                            key={
                                                                                targetType
                                                                            }
                                                                            value={
                                                                                targetType
                                                                            }
                                                                        >
                                                                            {
                                                                                targetType
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                            <Input
                                                                value={
                                                                    target.target_ref
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateTarget(
                                                                        placementIndex,
                                                                        targetIndex,
                                                                        'target_ref',
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                                placeholder="ID target"
                                                            />
                                                            <Button
                                                                type="button"
                                                                variant="destructive"
                                                                size="sm"
                                                                onClick={() =>
                                                                    removeTarget(
                                                                        placementIndex,
                                                                        targetIndex,
                                                                    )
                                                                }
                                                            >
                                                                Hapus
                                                            </Button>
                                                        </div>
                                                    ),
                                                )}
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `placements.${placementIndex}.targets`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            {form.data.placements.length >
                                                1 && (
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() =>
                                                        removePlacement(
                                                            placementIndex,
                                                        )
                                                    }
                                                >
                                                    Hapus placement
                                                </Button>
                                            )}
                                        </div>
                                    ),
                                )}
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {editingId === null
                                        ? 'Simpan widget'
                                        : 'Simpan perubahan'}
                                </Button>
                                {editingId !== null && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={resetForm}
                                    >
                                        Batal
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div className="space-y-3">
                    {widgets.length === 0 ? (
                        <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                            Belum ada widget.
                        </p>
                    ) : (
                        widgets.map((widget) => (
                            <Card key={widget.id}>
                                <CardContent className="flex items-start justify-between gap-4 py-4">
                                    <div className="min-w-0 space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="font-medium">
                                                {widget.translations[0]
                                                    ?.title || 'Tanpa judul'}
                                            </h2>
                                            <Badge
                                                variant={
                                                    widget.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {widget.is_active
                                                    ? 'Aktif'
                                                    : 'Nonaktif'}
                                            </Badge>
                                            <Badge variant="outline">
                                                HtmlWidget
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {widget.placements
                                                .map(
                                                    (placement) =>
                                                        `${placement.position} · ${placement.scope}`,
                                                )
                                                .join(', ')}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => edit(widget)}
                                        >
                                            Ubah
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => destroy(widget)}
                                        >
                                            Hapus
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>
            </div>
        </>
    );
}
