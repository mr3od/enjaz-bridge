import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Agency settings',
        href: '/settings/agency',
    },
];

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

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.patch('/settings/agency', {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agency settings" />

            <h1 className="sr-only">Agency settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Agency information"
                        description="Update your agency details"
                    />

                    <form onSubmit={submit} className="space-y-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Agency name</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        value={form.data.name}
                                        name="name"
                                        required
                                        autoComplete="organization"
                                        placeholder="Agency name"
                                        onChange={(event) =>
                                            form.setData(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={form.errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="city">Agency city</Label>

                                    <Input
                                        id="city"
                                        className="mt-1 block w-full"
                                        value={form.data.city}
                                        name="city"
                                        placeholder="City"
                                        onChange={(event) =>
                                            form.setData(
                                                'city',
                                                event.target.value,
                                            )
                                        }
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={form.errors.city}
                                    />
                                </div>

                                <div className="rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                                    <p>
                                        <span className="font-medium text-foreground">Plan:</span>{' '}
                                        {agency.plan}
                                    </p>
                                    <p>
                                        <span className="font-medium text-foreground">Quota:</span>{' '}
                                        {agency.used_this_month}/{agency.monthly_quota} used ({agency.quota_remaining} remaining)
                                    </p>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={form.processing}>
                                        Save
                                    </Button>

                                    <Transition
                                        show={form.recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
