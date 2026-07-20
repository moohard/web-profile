import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes/admin';
import { update as updateAiConfig } from '@/routes/admin/settings/ai';

type AiConfigCard = {
    task: string;
    label: string;
    base_url: string;
    model: string;
    system_prompt: string;
    enabled: boolean;
    has_key: boolean;
};

type AiConfigForm = {
    base_url: string;
    model: string;
    system_prompt: string;
    enabled: boolean;
    api_key: string;
};

function AiTaskCard({ config }: { config: AiConfigCard }) {
    const form = useForm<AiConfigForm>({
        base_url: config.base_url,
        model: config.model,
        system_prompt: config.system_prompt,
        enabled: config.enabled,
        api_key: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.put(updateAiConfig.url(config.task), {
            preserveScroll: true,
            onSuccess: () => form.reset('api_key'),
        });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>{config.label}</CardTitle>
                <CardDescription>
                    Task: <code>{config.task}</code>
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1">
                        <Label htmlFor={`base_url-${config.task}`}>
                            Base URL
                        </Label>
                        <Input
                            id={`base_url-${config.task}`}
                            value={form.data.base_url}
                            onChange={(e) =>
                                form.setData('base_url', e.target.value)
                            }
                            placeholder="https://api.provider.com/v1"
                        />
                        <InputError message={form.errors.base_url} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor={`model-${config.task}`}>Model</Label>
                        <Input
                            id={`model-${config.task}`}
                            value={form.data.model}
                            onChange={(e) =>
                                form.setData('model', e.target.value)
                            }
                            placeholder="nama-model"
                        />
                        <InputError message={form.errors.model} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor={`api_key-${config.task}`}>
                            API Key
                        </Label>
                        <Input
                            id={`api_key-${config.task}`}
                            type="password"
                            value={form.data.api_key}
                            onChange={(e) =>
                                form.setData('api_key', e.target.value)
                            }
                            placeholder={
                                config.has_key
                                    ? '•••••••• (tersimpan — kosongkan untuk tetap)'
                                    : 'Masukkan API key'
                            }
                            autoComplete="off"
                        />
                        <InputError message={form.errors.api_key} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor={`system_prompt-${config.task}`}>
                            System Prompt
                        </Label>
                        <textarea
                            id={`system_prompt-${config.task}`}
                            value={form.data.system_prompt}
                            onChange={(e) =>
                                form.setData('system_prompt', e.target.value)
                            }
                            rows={3}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Instruksi sistem (opsional)"
                        />
                        <InputError message={form.errors.system_prompt} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id={`enabled-${config.task}`}
                            checked={form.data.enabled}
                            onCheckedChange={(checked) =>
                                form.setData('enabled', checked === true)
                            }
                        />
                        <Label htmlFor={`enabled-${config.task}`}>Aktif</Label>
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Menyimpan…' : 'Simpan'}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

export default function AiConfigIndex({
    configs,
}: {
    configs: AiConfigCard[];
}) {
    return (
        <>
            <Head title="Konfigurasi AI" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Konfigurasi AI</h1>
                    <p className="text-sm text-muted-foreground">
                        Atur provider AI per-tugas (Terjemahan, Koreksi Konten,
                        Penyesuaian Markup). API key disimpan terenkripsi dan
                        tidak pernah ditampilkan kembali.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {configs.map((config) => (
                        <AiTaskCard key={config.task} config={config} />
                    ))}
                </div>
            </div>
        </>
    );
}

AiConfigIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Konfigurasi AI', href: '/admin/settings/ai' },
    ],
};
