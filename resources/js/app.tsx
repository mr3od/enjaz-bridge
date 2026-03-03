import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { initializeTheme } from '@/hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

type SharedLocaleProps = {
    locale?: string;
    direction?: 'rtl' | 'ltr';
};

const syncDocumentI18n = (pageProps: SharedLocaleProps) => {
    const locale = pageProps.locale ?? 'ar';
    const direction = pageProps.direction ?? 'rtl';

    document.documentElement.lang = locale;
    document.documentElement.dir = direction;
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        syncDocumentI18n((props.initialPage.props ?? {}) as SharedLocaleProps);

        router.on('navigate', (event) => {
            syncDocumentI18n(
                (event.detail.page.props ?? {}) as SharedLocaleProps,
            );
        });

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
