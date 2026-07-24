import ContactForm from '@/components/public/contact-form';
import { TestimonialForm } from '@/components/public/testimonial-form';
import { Testimonials } from '@/components/public/testimonials';
import type { SeoProps } from '@/components/seo/meta-head';
import PublicLayout from '@/layouts/public-layout';
import type { PublicLayoutSharedProps } from '@/layouts/public-layout';
import { cn } from '@/lib/utils';

type PageProp = {
    id: number;
    title: string;
    content?: {
        html?: string;
    } | null;
};

type PageShowProps = PublicLayoutSharedProps & {
    page: PageProp;
    seo: SeoProps;
    templateKey:
        'default' | 'full-width' | 'landing' | 'contact' | 'testimonials';
    testimonials: Array<{
        id: number;
        author_name: string;
        author_title: string | null;
        content: string;
        photo_url: string | null;
    }>;
};

export default function PageShow(props: PageShowProps) {
    const { page, seo, templateKey, ...layoutProps } = props;

    // Hindari dua <h1>: bila hero aktif dan punya heading, hero yang menyediakan
    // <h1> utama, jadi judul halaman cukup dirender sebagai teks bagi pembaca layar.
    const heroProvidesHeading = Boolean(
        layoutProps.region?.hero?.enabled && layoutProps.region.hero.heading,
    );
    const templateClasses = {
        default: 'mx-auto max-w-3xl px-4 py-10 sm:px-6',
        'full-width': 'w-full px-4 py-10 sm:px-8',
        landing: 'mx-auto max-w-5xl px-4 py-14 sm:px-6',
        contact: 'mx-auto max-w-3xl px-4 py-10 sm:px-6',
        testimonials: 'mx-auto max-w-5xl px-4 py-10 sm:px-6',
    } as const;

    return (
        <PublicLayout {...layoutProps} {...seo}>
            <article
                data-template={templateKey}
                className={cn(templateClasses[templateKey])}
            >
                {heroProvidesHeading ? (
                    <p className="sr-only">{page.title}</p>
                ) : (
                    <h1 className="mb-8 text-4xl font-semibold tracking-tight">
                        {page.title}
                    </h1>
                )}
                {templateKey === 'contact' ? (
                    <ContactForm />
                ) : templateKey === 'testimonials' ? (
                    <div className="space-y-10">
                        <div
                            className="prose max-w-none"
                            dangerouslySetInnerHTML={{
                                __html: page.content?.html ?? '',
                            }}
                        />
                        <Testimonials testimonials={props.testimonials} />
                        <section
                            aria-labelledby="testimonial-form-heading"
                            className="mx-auto max-w-2xl"
                        >
                            <h2
                                id="testimonial-form-heading"
                                className="mb-4 text-2xl font-semibold"
                            >
                                Kirim testimoni
                            </h2>
                            <TestimonialForm />
                        </section>
                    </div>
                ) : (
                    <div
                        className="prose max-w-none"
                        dangerouslySetInnerHTML={{
                            __html: page.content?.html ?? '',
                        }}
                    />
                )}
            </article>
        </PublicLayout>
    );
}
