import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: string;
            direction: 'rtl' | 'ltr';
            translations: {
                ui: Record<string, string>;
                [key: string]: Record<string, string>;
            };
            [key: string]: unknown;
        };
    }
}
