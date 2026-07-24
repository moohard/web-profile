type Testimonial = {
    id: number;
    author_name: string;
    author_title: string | null;
    content: string;
    photo_url: string | null;
};

export function Testimonials({
    testimonials,
}: {
    testimonials: Testimonial[];
}) {
    if (testimonials.length === 0) {
        return (
            <p className="text-muted-foreground">
                Belum ada testimoni yang ditampilkan.
            </p>
        );
    }

    return (
        <section aria-label="Testimoni" className="grid gap-4 md:grid-cols-2">
            {testimonials.map((testimonial) => (
                <figure key={testimonial.id} className="rounded-lg border p-6">
                    <blockquote className="text-pretty">
                        “{testimonial.content}”
                    </blockquote>
                    <figcaption className="mt-4 flex items-center gap-3 text-sm text-muted-foreground">
                        {testimonial.photo_url && (
                            <img
                                src={testimonial.photo_url}
                                alt=""
                                className="h-10 w-10 rounded-full object-cover"
                            />
                        )}
                        <span>
                            <span className="block font-medium text-foreground">
                                {testimonial.author_name}
                            </span>
                            {testimonial.author_title}
                        </span>
                    </figcaption>
                </figure>
            ))}
        </section>
    );
}
