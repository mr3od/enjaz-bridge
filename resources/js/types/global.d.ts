import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            translations: {
                ui: Record<string, string>;
                [key: string]: Record<string, string>;
            };
            [key: string]: unknown;
        };
    }
}
