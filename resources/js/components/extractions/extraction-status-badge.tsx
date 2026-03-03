import { Badge } from '@/components/ui/badge';
import { useI18n } from '@/hooks/use-i18n';
import type { ExtractionStatus } from '@/types';

const statusMeta: Record<
    ExtractionStatus,
    { label: string; className: string }
> = {
    draft: {
        label: 'Draft',
        className:
            'border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
    },
    queued: {
        label: 'Queued',
        className:
            'border-amber-300 bg-amber-100 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300',
    },
    processing: {
        label: 'Processing',
        className:
            'border-blue-300 bg-blue-100 text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300',
    },
    extracted: {
        label: 'Extracted',
        className:
            'border-emerald-300 bg-emerald-100 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    },
    failed: {
        label: 'Failed',
        className:
            'border-rose-300 bg-rose-100 text-rose-900 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-300',
    },
};

export default function ExtractionStatusBadge({
    status,
}: {
    status: ExtractionStatus;
}) {
    const { t } = useI18n();
    const meta = statusMeta[status] ?? statusMeta.draft;
    const translatedLabel = t(`ui.${status}`);
    const label = translatedLabel.startsWith('ui.')
        ? meta.label
        : translatedLabel;

    return <Badge className={meta.className}>{label}</Badge>;
}
