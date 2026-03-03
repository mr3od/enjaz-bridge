import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/hooks/use-i18n';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit, update } from '@/routes/agency';
import type { BreadcrumbItem } from '@/types';

type AgencySettingsProps = {
    agency: {
        name: string;
        city: string | null;
        plan: string;
        monthly_quota: number;
        used_this_month: number;
        quota_remaining: number;
        quota_resets_at: string | null;
    };
};

export default function Agency({ agency }: AgencySettingsProps) {
    const form = useForm({
        name: agency.name,
        city: agency.city ?? '',
    });
    const { t } = useI18n();
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('settings_agency_breadcrumb'),
            href: edit(),
        },
    ];

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.patch(update.url(), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('settings_agency_breadcrumb')} />

            <h1 className="sr-only">{t('settings_agency_breadcrumb')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('settings_agency_heading')}
                        description={t('settings_agency_description')}
                    />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                {t('settings_agency_name')}
                            </Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={form.data.name}
                                name="name"
                                required
                                autoComplete="organization"
                                placeholder={t('settings_agency_name')}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />

                            <InputError
                                className="mt-2"
                                message={form.errors.name}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="city">
                                {t('settings_agency_city')}
                            </Label>

                            <Input
                                id="city"
                                className="mt-1 block w-full"
                                value={form.data.city}
                                name="city"
                                placeholder={t('settings_city')}
                                onChange={(event) =>
                                    form.setData('city', event.target.value)
                                }
                            />

                            <InputError
                                className="mt-2"
                                message={form.errors.city}
                            />
                        </div>

                        <div className="rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                            <p>
                                <span className="font-medium text-foreground">
                                    {t('settings_plan')}:
                                </span>{' '}
                                {agency.plan}
                            </p>
                            <p>
                                <span className="font-medium text-foreground">
                                    {t('settings_quota')}:
                                </span>{' '}
                                {t('settings_quota_summary', {
                                    used: agency.used_this_month,
                                    total: agency.monthly_quota,
                                    remaining: agency.quota_remaining,
                                })}
                            </p>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={form.processing}>
                                {t('save')}
                            </Button>

                            <Transition
                                show={form.recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">
                                    {t('saved')}
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
