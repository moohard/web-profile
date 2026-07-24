import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/contact';

type ContactFormData = {
    name: string;
    email: string;
    phone: string;
    subject: string;
    message: string;
    website: string;
};

export default function ContactForm() {
    const form = useForm<ContactFormData>({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
        website: '',
    });

    function submit() {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                submit();
            }}
            className="space-y-5"
            noValidate
        >
            <div className="grid gap-5 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="contact-name">Nama</Label>
                    <Input
                        id="contact-name"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        autoComplete="name"
                        required
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="contact-email">Email</Label>
                    <Input
                        id="contact-email"
                        type="email"
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                        autoComplete="email"
                        required
                    />
                    <InputError message={form.errors.email} />
                </div>
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="contact-phone">Telepon (opsional)</Label>
                    <Input
                        id="contact-phone"
                        type="tel"
                        value={form.data.phone}
                        onChange={(event) =>
                            form.setData('phone', event.target.value)
                        }
                        autoComplete="tel"
                    />
                    <InputError message={form.errors.phone} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="contact-subject">Subjek (opsional)</Label>
                    <Input
                        id="contact-subject"
                        value={form.data.subject}
                        onChange={(event) =>
                            form.setData('subject', event.target.value)
                        }
                    />
                    <InputError message={form.errors.subject} />
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="contact-message">Pesan</Label>
                <textarea
                    id="contact-message"
                    value={form.data.message}
                    onChange={(event) =>
                        form.setData('message', event.target.value)
                    }
                    className="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    required
                />
                <InputError message={form.errors.message} />
            </div>

            <div className="hidden" aria-hidden="true">
                <Label htmlFor="contact-website">Website</Label>
                <Input
                    id="contact-website"
                    tabIndex={-1}
                    autoComplete="off"
                    value={form.data.website}
                    onChange={(event) =>
                        form.setData('website', event.target.value)
                    }
                />
            </div>

            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Mengirim…' : 'Kirim pesan'}
            </Button>
        </form>
    );
}
