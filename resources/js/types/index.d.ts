import { Config } from "ziggy-js";

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    shipping_address?: string | null;
    is_admin?: boolean;
    email_verified_at?: string;
}

export interface FlashCart {
    code: string;
    pdf_url: string;
    subtotal?: number | string;
    discount_amount?: number | string;
    total: number | string;
    coupon_code?: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
        cart?: FlashCart | null;
    };
    branding: {
        site_logo?: string | null;
        site_favicon?: string | null;
        site_name?: string | null;
    };
    store_nav_groups?: Array<{
        label: string;
        items: Array<{
            label: string;
            href: string;
        }>;
    }>;
    ziggy: Config & { location: string };
};
