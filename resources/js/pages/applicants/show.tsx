import { Transition } from '@headlessui/react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ExternalLink, RefreshCcw } from 'lucide-react';
import type { FormEvent } from 'react';
import ApplicantReviewController from '@/actions/App/Http/Controllers/ApplicantReviewController';
import PassportExtractionController from '@/actions/App/Http/Controllers/PassportExtractionController';
import ExtractionStatusBadge from '@/components/extractions/extraction-status-badge';
import InputError from '@/components/input-error';
import { useI18n } from '@/hooks/use-i18n';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { ApplicantReviewData, BreadcrumbItem } from '@/types';

type ApplicantReviewProps = {
    applicant: ApplicantReviewData;
    latest_extraction: {
        model_used: string;
        processing_ms: number;
        created_at: string | null;
    } | null;
};

type ApplicantFormData = {
    passport_number: string;
    country_code: string;
    surname_ar: string;
    given_names_ar: string;
    surname_en: string;
    given_names_en: string;
    date_of_birth: string;
    place_of_birth_ar: string;
    place_of_birth_en: string;
    sex: string;
    date_of_issue: string;
    date_of_expiry: string;
    profession_ar: string;
    profession_en: string;
    issuing_authority_ar: string;
    issuing_authority_en: string;
};

const formatDateTime = (value: string | null): string => {
    if (value === null) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-GB', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const reviewFieldRows: Array<
    Array<{
        key: keyof ApplicantFormData;
        labelKey: string;
        type?: 'text' | 'date';
    }>
> = [
    [
        { key: 'passport_number', labelKey: 'ui.field_passport_number' },
        { key: 'country_code', labelKey: 'ui.field_country_code' },
    ],
    [
        { key: 'surname_ar', labelKey: 'ui.field_surname_ar' },
        { key: 'surname_en', labelKey: 'ui.field_surname_en' },
    ],
    [
        { key: 'given_names_ar', labelKey: 'ui.field_given_names_ar' },
        { key: 'given_names_en', labelKey: 'ui.field_given_names_en' },
    ],
    [
        { key: 'sex', labelKey: 'ui.field_sex' },
        {
            key: 'date_of_birth',
            labelKey: 'ui.field_date_of_birth',
            type: 'date',
        },
    ],
    [
        { key: 'place_of_birth_ar', labelKey: 'ui.field_place_of_birth_ar' },
        { key: 'place_of_birth_en', labelKey: 'ui.field_place_of_birth_en' },
    ],
    [
        {
            key: 'date_of_issue',
            labelKey: 'ui.field_date_of_issue',
            type: 'date',
        },
        {
            key: 'date_of_expiry',
            labelKey: 'ui.field_date_of_expiry',
            type: 'date',
        },
    ],
    [
        { key: 'profession_ar', labelKey: 'ui.field_profession_ar' },
        { key: 'profession_en', labelKey: 'ui.field_profession_en' },
    ],
    [
        {
            key: 'issuing_authority_ar',
            labelKey: 'ui.field_issuing_authority_ar',
        },
        {
            key: 'issuing_authority_en',
            labelKey: 'ui.field_issuing_authority_en',
        },
    ],
];

export default function ApplicantShow({
    applicant,
    latest_extraction,
}: ApplicantReviewProps) {
    const { t } = useI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('ui.passport_extraction'),
            href: PassportExtractionController.index(),
        },
        {
            title: applicant.passport_number ?? applicant.id,
            href: ApplicantReviewController.show(applicant.id),
        },
    ];

    const form = useForm<ApplicantFormData>({
        passport_number: applicant.passport_number ?? '',
        country_code: applicant.country_code ?? '',
        surname_ar: applicant.surname_ar ?? '',
        given_names_ar: applicant.given_names_ar ?? '',
        surname_en: applicant.surname_en ?? '',
        given_names_en: applicant.given_names_en ?? '',
        date_of_birth: applicant.date_of_birth ?? '',
        place_of_birth_ar: applicant.place_of_birth_ar ?? '',
        place_of_birth_en: applicant.place_of_birth_en ?? '',
        sex: applicant.sex ?? '',
        date_of_issue: applicant.date_of_issue ?? '',
        date_of_expiry: applicant.date_of_expiry ?? '',
        profession_ar: applicant.profession_ar ?? '',
        profession_en: applicant.profession_en ?? '',
        issuing_authority_ar: applicant.issuing_authority_ar ?? '',
        issuing_authority_en: applicant.issuing_authority_en ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        form.patch(ApplicantReviewController.update.url(applicant.id), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`${t('ui.review_title')} ${applicant.passport_number ?? applicant.id}`}
            />

            <div className="space-y-6 p-4">
                {applicant.extraction_error && (
                    <Alert variant="destructive">
                        <AlertTitle>{t('ui.error')}</AlertTitle>
                        <AlertDescription>
                            {applicant.extraction_error}
                        </AlertDescription>
                    </Alert>
                )}

                {applicant.passport_image_url === null && (
                    <Alert variant="destructive">
                        <AlertTitle>
                            {t('ui.missing_passport_image')}
                        </AlertTitle>
                        <AlertDescription>
                            {t('ui.missing_passport_image_description')}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <Card>
                        <CardHeader className="space-y-3">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <CardTitle>
                                        {t('ui.review_title')}
                                    </CardTitle>
                                    <CardDescription>
                                        {t('ui.review_description')}
                                    </CardDescription>
                                </div>
                                <ExtractionStatusBadge
                                    status={applicant.status}
                                />
                            </div>
                        </CardHeader>

                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                {reviewFieldRows.map((row, rowIndex) => (
                                    <div
                                        key={`review-row-${rowIndex + 1}`}
                                        className="grid gap-4 md:grid-cols-2"
                                    >
                                        {row.map((field) => (
                                            <div
                                                className="space-y-2"
                                                key={field.key}
                                            >
                                                <Label htmlFor={field.key}>
                                                    {t(field.labelKey)}
                                                </Label>
                                                <Input
                                                    id={field.key}
                                                    type={field.type ?? 'text'}
                                                    value={String(
                                                        form.data[field.key] ??
                                                            '',
                                                    )}
                                                    onChange={(event) =>
                                                        form.setData(
                                                            field.key,
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[field.key]
                                                    }
                                                />
                                            </div>
                                        ))}
                                    </div>
                                ))}

                                <div className="flex items-center gap-3 pt-2">
                                    <Button
                                        disabled={
                                            form.processing ||
                                            applicant.passport_image_url ===
                                                null
                                        }
                                    >
                                        {t('ui.save_corrections')}
                                    </Button>
                                    <Transition
                                        show={form.recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-muted-foreground">
                                            {t('ui.saved')}
                                        </p>
                                    </Transition>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('ui.source_image')}</CardTitle>
                                <CardDescription>
                                    {t('ui.source_image_description')}
                                </CardDescription>
                            </CardHeader>

                            <CardContent className="space-y-3">
                                {applicant.passport_image_url === null ? (
                                    <div className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                                        {t('ui.no_passport_image')}
                                    </div>
                                ) : (
                                    <>
                                        <a
                                            href={applicant.passport_image_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="block overflow-hidden rounded-lg border"
                                        >
                                            <img
                                                src={
                                                    applicant.passport_image_url
                                                }
                                                alt="Uploaded passport"
                                                className="h-auto w-full object-cover"
                                            />
                                        </a>

                                        <Button
                                            variant="outline"
                                            className="w-full"
                                            asChild
                                        >
                                            <a
                                                href={
                                                    applicant.passport_image_url
                                                }
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <ExternalLink className="mr-2 h-4 w-4" />
                                                {t('ui.open_full_size')}
                                            </a>
                                        </Button>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    {t('ui.extraction_details')}
                                </CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-3 text-sm">
                                <div className="space-y-1">
                                    <p className="text-muted-foreground">
                                        {t('ui.requested')}
                                    </p>
                                    <p>
                                        {formatDateTime(
                                            applicant.extraction_requested_at,
                                        )}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground">
                                        {t('ui.started')}
                                    </p>
                                    <p>
                                        {formatDateTime(
                                            applicant.extraction_started_at,
                                        )}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground">
                                        {t('ui.finished')}
                                    </p>
                                    <p>
                                        {formatDateTime(
                                            applicant.extraction_finished_at,
                                        )}
                                    </p>
                                </div>

                                <div className="space-y-1">
                                    <p className="text-muted-foreground">
                                        {t('ui.model')}
                                    </p>
                                    <p>
                                        {latest_extraction?.model_used ?? '-'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground">
                                        {t('ui.processing_time')}
                                    </p>
                                    <p>
                                        {latest_extraction !== null
                                            ? `${latest_extraction.processing_ms} ms`
                                            : '-'}
                                    </p>
                                </div>

                                <div className="pt-2">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        className="w-full"
                                        onClick={() =>
                                            router.post(
                                                ApplicantReviewController.reExtract.url(
                                                    applicant.id,
                                                ),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <RefreshCcw className="mr-2 h-4 w-4" />
                                        {t('ui.re_extract')}
                                    </Button>
                                </div>

                                <div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full"
                                        asChild
                                    >
                                        <Link
                                            href={PassportExtractionController.index.url()}
                                        >
                                            {t('ui.back_to_queue')}
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
