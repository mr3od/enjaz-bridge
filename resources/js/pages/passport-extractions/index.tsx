import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ChangeEvent,
    DragEvent,
    FormEvent,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import {
    AlertCircle,
    CheckCircle2,
    Eye,
    FileUp,
    RefreshCcw,
} from 'lucide-react';
import ApplicantReviewController from '@/actions/App/Http/Controllers/ApplicantReviewController';
import PassportExtractionController from '@/actions/App/Http/Controllers/PassportExtractionController';
import ExtractionStatusBadge from '@/components/extractions/extraction-status-badge';
import QuotaMeter from '@/components/extractions/quota-meter';
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
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    ExtractionQueueItem,
    ExtractionQuotaSummary,
} from '@/types';

type PassportExtractionIndexProps = {
    applicants: ExtractionQueueItem[];
    quota: ExtractionQuotaSummary;
    batch_limit: number;
    max_file_kb: number;
    flash?: {
        queued_count?: number;
    };
};

const isActiveStatus = (status: string): boolean =>
    status === 'queued' || status === 'processing';

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

const formatFileSize = (sizeInBytes: number): string => {
    if (sizeInBytes < 1024) {
        return `${sizeInBytes} B`;
    }

    if (sizeInBytes < 1024 * 1024) {
        return `${(sizeInBytes / 1024).toFixed(1)} KB`;
    }

    return `${(sizeInBytes / (1024 * 1024)).toFixed(1)} MB`;
};

export default function PassportExtractionIndex({
    applicants,
    quota: initialQuota,
    batch_limit,
    max_file_kb,
    flash,
}: PassportExtractionIndexProps) {
    const { t, isRtl } = useI18n();
    const [queueItems, setQueueItems] =
        useState<ExtractionQueueItem[]>(applicants);
    const [quota, setQuota] = useState<ExtractionQuotaSummary>(initialQuota);
    const [isDragging, setIsDragging] = useState(false);
    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
    const inputRef = useRef<HTMLInputElement | null>(null);

    const form = useForm<{ files: File[] }>({
        files: [],
    });

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: t('ui.passport_extraction'),
                href: PassportExtractionController.index(),
            },
        ],
        [t],
    );

    useEffect(() => {
        setQueueItems(applicants);
    }, [applicants]);

    useEffect(() => {
        setQuota(initialQuota);
    }, [initialQuota]);

    const hasActiveRows = useMemo(
        () => queueItems.some((item) => isActiveStatus(item.status)),
        [queueItems],
    );

    useEffect(() => {
        if (!hasActiveRows) {
            return;
        }

        const interval = window.setInterval(async () => {
            const ids = queueItems.map((item) => item.id);

            if (ids.length === 0) {
                return;
            }

            const endpoint = PassportExtractionController.status.url({
                query: { ids },
            });

            const response = await fetch(endpoint, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = (await response.json()) as {
                applicants: ExtractionQueueItem[];
                quota: ExtractionQuotaSummary;
            };

            setQueueItems(payload.applicants);
            setQuota(payload.quota);
        }, 2000);

        return () => {
            window.clearInterval(interval);
        };
    }, [hasActiveRows, queueItems]);

    const updateFiles = (files: File[]): void => {
        const nextFiles = files.slice(0, batch_limit);
        setSelectedFiles(nextFiles);
        form.setData('files', nextFiles);
    };

    const handleFileInput = (event: ChangeEvent<HTMLInputElement>): void => {
        updateFiles(Array.from(event.target.files ?? []));
    };

    const handleDrop = (event: DragEvent<HTMLDivElement>): void => {
        event.preventDefault();
        event.stopPropagation();
        setIsDragging(false);
        updateFiles(Array.from(event.dataTransfer.files));
    };

    const handleDragOver = (event: DragEvent<HTMLDivElement>): void => {
        event.preventDefault();
        event.stopPropagation();
        setIsDragging(true);
    };

    const handleDragLeave = (event: DragEvent<HTMLDivElement>): void => {
        event.preventDefault();
        event.stopPropagation();
        setIsDragging(false);
    };

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        form.post(PassportExtractionController.store.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setSelectedFiles([]);
                form.setData('files', []);
                if (inputRef.current !== null) {
                    inputRef.current.value = '';
                }
            },
        });
    };

    const retry = (applicantId: string): void => {
        router.post(
            ApplicantReviewController.reExtract.url(applicantId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setQueueItems((previous) =>
                        previous.map((item) =>
                            item.id === applicantId
                                ? {
                                      ...item,
                                      status: 'queued',
                                      extraction_error: null,
                                      extraction_requested_at:
                                          new Date().toISOString(),
                                      extraction_started_at: null,
                                      extraction_finished_at: null,
                                  }
                                : item,
                        ),
                    );
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('ui.passport_extraction')} />

            <div className="space-y-6 p-4">
                {flash?.queued_count !== undefined &&
                    flash.queued_count > 0 && (
                        <Alert>
                            <CheckCircle2 className="h-4 w-4" />
                            <AlertTitle>
                                {t('ui.files_queued_title')}
                            </AlertTitle>
                            <AlertDescription>
                                {t('ui.files_queued_description', {
                                    count: flash.queued_count,
                                })}
                            </AlertDescription>
                        </Alert>
                    )}

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('ui.batch_upload_title')}</CardTitle>
                            <CardDescription>
                                {t('ui.batch_upload_description', {
                                    count: batch_limit,
                                    size: Math.floor(max_file_kb / 1024),
                                })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div
                                    className={`rounded-xl border border-dashed p-8 text-center transition ${
                                        isDragging
                                            ? 'border-primary bg-primary/5'
                                            : 'border-border bg-muted/30'
                                    }`}
                                    onDrop={handleDrop}
                                    onDragOver={handleDragOver}
                                    onDragLeave={handleDragLeave}
                                >
                                    <FileUp className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
                                    <p className="font-medium">
                                        {t('ui.drop_files')}
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {t('ui.or_choose_files')}
                                    </p>

                                    <div className="mt-4">
                                        <input
                                            ref={inputRef}
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            multiple
                                            onChange={handleFileInput}
                                            className="hidden"
                                            id="passport-files"
                                        />
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() =>
                                                inputRef.current?.click()
                                            }
                                        >
                                            {t('ui.choose_files')}
                                        </Button>
                                    </div>
                                </div>

                                {selectedFiles.length > 0 && (
                                    <div className="space-y-2 rounded-lg border p-3">
                                        <p className="text-sm font-medium">
                                            {t('ui.selected_files')}
                                        </p>
                                        <ul className="space-y-1 text-sm text-muted-foreground">
                                            {selectedFiles.map((file) => (
                                                <li
                                                    key={`${file.name}-${file.size}`}
                                                >
                                                    {file.name} (
                                                    {formatFileSize(file.size)})
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                <InputError message={form.errors.files} />

                                <div className="flex items-center gap-3">
                                    <Button
                                        type="submit"
                                        disabled={
                                            form.processing ||
                                            selectedFiles.length === 0
                                        }
                                    >
                                        {form.processing && (
                                            <Spinner className="mr-2" />
                                        )}
                                        {t('ui.queue_extraction')}
                                    </Button>

                                    {form.progress && (
                                        <p className="text-sm text-muted-foreground">
                                            {t('ui.uploading')}{' '}
                                            {form.progress.percentage}%
                                        </p>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <QuotaMeter quota={quota} />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('ui.queue_title')}</CardTitle>
                        <CardDescription>
                            {t('ui.queue_description')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {queueItems.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                {t('ui.no_extraction_records')}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[920px] text-sm">
                                    <thead>
                                        <tr className="border-b text-start text-xs tracking-wide text-muted-foreground uppercase">
                                            <th className="px-2 py-3">
                                                {t('ui.preview_name')}
                                            </th>
                                            <th className="px-2 py-3">
                                                {t('ui.status')}
                                            </th>
                                            <th className="px-2 py-3">
                                                {t('ui.started')}
                                            </th>
                                            <th className="px-2 py-3">
                                                {t('ui.finished')}
                                            </th>
                                            <th className="px-2 py-3">
                                                {t('ui.error')}
                                            </th>
                                            <th className="px-2 py-3">
                                                {t('ui.actions')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {queueItems.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-b align-top"
                                            >
                                                <td className="px-2 py-3">
                                                    <div className="flex items-start gap-3">
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-md border bg-muted text-xs font-semibold">
                                                            {item.passport_number?.slice(
                                                                0,
                                                                2,
                                                            ) ?? 'PP'}
                                                        </div>
                                                        <div
                                                            className={
                                                                isRtl
                                                                    ? 'text-right'
                                                                    : 'text-left'
                                                            }
                                                        >
                                                            <p className="font-medium">
                                                                {item.surname_en ??
                                                                    '-'}{' '}
                                                                {item.given_names_en ??
                                                                    ''}
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {item.surname_ar ??
                                                                    '-'}{' '}
                                                                {item.given_names_ar ??
                                                                    ''}
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                ID: {item.id}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-2 py-3">
                                                    <div className="flex items-center gap-2">
                                                        <ExtractionStatusBadge
                                                            status={item.status}
                                                        />
                                                        {isActiveStatus(
                                                            item.status,
                                                        ) && <Spinner />}
                                                    </div>
                                                </td>
                                                <td className="px-2 py-3 text-muted-foreground">
                                                    {formatDateTime(
                                                        item.extraction_started_at,
                                                    )}
                                                </td>
                                                <td className="px-2 py-3 text-muted-foreground">
                                                    {formatDateTime(
                                                        item.extraction_finished_at,
                                                    )}
                                                </td>
                                                <td className="px-2 py-3">
                                                    {item.extraction_error ? (
                                                        <div className="flex max-w-xs items-start gap-2 text-rose-700 dark:text-rose-300">
                                                            <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                                            <span className="line-clamp-3 text-xs">
                                                                {
                                                                    item.extraction_error
                                                                }
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            -
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-2 py-3">
                                                    <div className="flex items-center gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={ApplicantReviewController.show.url(
                                                                    item.id,
                                                                )}
                                                            >
                                                                <Eye className="mr-1 h-3.5 w-3.5" />
                                                                {t('ui.open')}
                                                            </Link>
                                                        </Button>

                                                        {item.status ===
                                                            'extracted' && (
                                                            <Button
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={ApplicantReviewController.show.url(
                                                                        item.id,
                                                                    )}
                                                                >
                                                                    {t(
                                                                        'ui.review',
                                                                    )}
                                                                </Link>
                                                            </Button>
                                                        )}

                                                        {item.status ===
                                                            'failed' && (
                                                            <Button
                                                                size="sm"
                                                                variant="secondary"
                                                                onClick={() =>
                                                                    retry(
                                                                        item.id,
                                                                    )
                                                                }
                                                            >
                                                                <RefreshCcw className="mr-1 h-3.5 w-3.5" />
                                                                {t('ui.retry')}
                                                            </Button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
