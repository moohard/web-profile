type HeroProps = {
    image?: string;
    heading?: string;
    subheading?: string;
    ctaText?: string;
    ctaLink?: string;
};

/**
 * Region hero opsional di layout publik.
 */
export function Hero({
    image,
    heading,
    subheading,
    ctaText,
    ctaLink,
}: HeroProps) {
    return (
        <section
            aria-label="Hero"
            className="relative min-h-[300px] overflow-hidden bg-slate-900 text-white"
        >
            {image && (
                <img
                    src={image}
                    alt=""
                    className="absolute inset-0 h-full w-full object-cover opacity-50"
                    loading="eager"
                />
            )}
            <div className="relative z-10 mx-auto max-w-6xl p-8 md:p-16">
                {heading && (
                    <h1 className="text-3xl font-bold md:text-5xl">
                        {heading}
                    </h1>
                )}
                {subheading && (
                    <p className="mt-4 text-lg text-white/90">{subheading}</p>
                )}
                {ctaText && ctaLink && (
                    <a
                        href={ctaLink}
                        className="mt-6 inline-block rounded bg-primary px-6 py-3 font-semibold hover:bg-primary/90"
                    >
                        {ctaText}
                    </a>
                )}
            </div>
        </section>
    );
}
