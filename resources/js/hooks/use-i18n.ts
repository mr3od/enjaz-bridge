import { usePage } from '@inertiajs/react';

type SharedTranslations = {
    ui?: Record<string, string>;
};

type SharedI18nProps = {
    locale?: string;
    direction?: 'rtl' | 'ltr';
    translations?: SharedTranslations;
};

export function useI18n() {
    const page = usePage<SharedI18nProps>();
    const locale = page.props.locale ?? 'ar';
    const direction = page.props.direction ?? 'rtl';
    const translations = page.props.translations ?? {};

    const t = (
        key: string,
        replacements: Record<string, string | number> = {},
    ): string => {
        const [scope, innerKey] = key.includes('.')
            ? key.split('.', 2)
            : ['ui', key];
        let text =
            translations[scope as keyof SharedTranslations]?.[innerKey] ?? key;

        Object.entries(replacements).forEach(([placeholder, value]) => {
            text = text.replaceAll(`:${placeholder}`, String(value));
        });

        return text;
    };

    return {
        locale,
        direction,
        isRtl: direction === 'rtl',
        t,
    };
}
