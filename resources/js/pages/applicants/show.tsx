import { Transition } from '@headlessui/react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { RefreshCcw } from 'lucide-react';
import type { FormEvent } from 'react';
import ApplicantReviewController from '@/actions/App/Http/Controllers/ApplicantReviewController';
import PassportExtractionController from '@/actions/App/Http/Controllers/PassportExtractionController';
import ExtractionStatusBadge from '@/components/extractions/extraction-status-badge';
import InputError from '@/components/input-error';
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
    mrz_line_1: string;
    mrz_line_2: string;
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

const fieldRows: Array<{
    key: keyof ApplicantFormData;
    label: string;
    type?: 'text' | 'date';
}> = [
    { key: 'passport_number', label: 'Passport Number / رقم الجواز' },
    { key: 'country_code', label: 'Country Code / رمز الدولة' },
    { key: 'sex', label: 'Sex / الجنس' },
    { key: 'mrz_line_1', label: 'MRZ Line 1 / سطر MRZ الأول' },
    { key: 'mrz_line_2', label: 'MRZ Line 2 / سطر MRZ الثاني' },
    { key: 'surname_en', label: 'Surname (EN) / اللقب (إنجليزي)' },
    { key: 'given_names_en', label: 'Given Names (EN) / الأسماء (إنجليزي)' },
    { key: 'surname_ar', label: 'Surname (AR) / اللقب (عربي)' },
    { key: 'given_names_ar', label: 'Given Names (AR) / الأسماء (عربي)' },
    {
        key: 'date_of_birth',
        label: 'Date of Birth / تاريخ الميلاد',
        type: 'date',
    },
    {
        key: 'place_of_birth_en',
        label: 'Place of Birth (EN) / مكان الميلاد (إنجليزي)',
    },
    {
        key: 'place_of_birth_ar',
        label: 'Place of Birth (AR) / مكان الميلاد (عربي)',
    },
    {
        key: 'date_of_issue',
        label: 'Date of Issue / تاريخ الإصدار',
        type: 'date',
    },
    {
        key: 'date_of_expiry',
        label: 'Date of Expiry / تاريخ الانتهاء',
        type: 'date',
    },
    { key: 'profession_en', label: 'Profession (EN) / المهنة (إنجليزي)' },
    { key: 'profession_ar', label: 'Profession (AR) / المهنة (عربي)' },
    {
        key: 'issuing_authority_en',
        label: 'Issuing Authority (EN) / جهة الإصدار (إنجليزي)',
    },
    {
        key: 'issuing_authority_ar',
        label: 'Issuing Authority (AR) / جهة الإصدار (عربي)',
    },
];

export default function ApplicantShow({
    applicant,
    latest_extraction,
}: ApplicantReviewProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Passport Extraction',
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
        mrz_line_1: applicant.mrz_line_1 ?? '',
        mrz_line_2: applicant.mrz_line_2 ?? '',
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
                title={`Applicant ${applicant.passport_number ?? applicant.id}`}
            />

            <div className="space-y-6 p-4">
                {applicant.extraction_error && (
                    <Alert variant="destructive">
                        <AlertTitle>Extraction error</AlertTitle>
                        <AlertDescription>
                            {applicant.extraction_error}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <Card>
                        <CardHeader className="space-y-3">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <CardTitle>
                                        Applicant Review / مراجعة المتقدم
                                    </CardTitle>
                                    <CardDescription>
                                        Review AI extraction output, then save
                                        corrected fields.
                                    </CardDescription>
                                </div>
                                <ExtractionStatusBadge
                                    status={applicant.status}
                                />
                            </div>
                        </CardHeader>

                        <CardContent>
                            <form
                                onSubmit={submit}
                                className="grid gap-4 md:grid-cols-2"
                            >
                                {fieldRows.map((field) => (
                                    <div className="space-y-2" key={field.key}>
                                        <Label htmlFor={field.key}>
                                            {field.label}
                                        </Label>
                                        <Input
                                            id={field.key}
                                            type={field.type ?? 'text'}
                                            value={String(
                                                form.data[field.key] ?? '',
                                            )}
                                            onChange={(event) =>
                                                form.setData(
                                                    field.key,
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors[field.key]}
                                        />
                                    </div>
                                ))}

                                <div className="col-span-full flex items-center gap-3 pt-2">
                                    <Button disabled={form.processing}>
                                        Save corrections
                                    </Button>
                                    <Transition
                                        show={form.recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-muted-foreground">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Extraction Details / تفاصيل الاستخراج
                            </CardTitle>
                        </CardHeader>

                        <CardContent className="space-y-3 text-sm">
                            <div className="space-y-1">
                                <p className="text-muted-foreground">
                                    Requested
                                </p>
                                <p>
                                    {formatDateTime(
                                        applicant.extraction_requested_at,
                                    )}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-muted-foreground">Started</p>
                                <p>
                                    {formatDateTime(
                                        applicant.extraction_started_at,
                                    )}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-muted-foreground">
                                    Finished
                                </p>
                                <p>
                                    {formatDateTime(
                                        applicant.extraction_finished_at,
                                    )}
                                </p>
                            </div>

                            <div className="space-y-1">
                                <p className="text-muted-foreground">Model</p>
                                <p>{latest_extraction?.model_used ?? '-'}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-muted-foreground">
                                    Processing time
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
                                    Re-extract
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
                                        Back to queue
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
