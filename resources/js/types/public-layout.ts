export type PublicLayoutProps = {
    whatsapp: {
        number: string | null;
        enabled: boolean;
        default_message: string | null;
    };
    footer: {
        text: string | null;
        address: string | null;
        phone: string;
        email: string | null;
        social_links: Record<string, string>;
    };
    rating: {
        totalRespondents: number;
        criteria: {
            id: number;
            name: string;
            average: number;
            total: number;
        }[];
    };
};
