import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useI18n();

    const appearanceTabs: {
        value: Appearance;
        icon: LucideIcon;
        label: string;
    }[] = [
        { value: 'light', icon: Sun, label: t('settings_appearance_light') },
        { value: 'dark', icon: Moon, label: t('settings_appearance_dark') },
        {
            value: 'system',
            icon: Monitor,
            label: t('settings_appearance_system'),
        },
    ];

    const baseButtonClass =
        'flex items-center rounded-md px-3.5 py-1.5 transition-colors';
    const activeButtonClass =
        'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100';
    const inactiveButtonClass =
        'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60';

    return (
        <div className={cn('space-y-2', className)} {...props}>
            <p className="text-sm font-medium text-foreground">
                {t('settings_appearance_mode_heading')}
            </p>
            <div className="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
                {appearanceTabs.map(({ value, icon: Icon, label }) => (
                    <button
                        key={value}
                        onClick={() => updateAppearance(value)}
                        className={cn(
                            baseButtonClass,
                            appearance === value
                                ? activeButtonClass
                                : inactiveButtonClass,
                        )}
                    >
                        <Icon className="-ml-1 h-4 w-4" />
                        <span className="ml-1.5 text-sm">{label}</span>
                    </button>
                ))}
            </div>
        </div>
    );
}
