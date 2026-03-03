import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import type { ExtractionQuotaSummary } from '@/types';

export default function QuotaMeter({
    quota,
}: {
    quota: ExtractionQuotaSummary;
}) {
    const { t, isRtl } = useI18n();

    const percentage =
        quota.monthly_quota > 0
            ? Math.min(
                  100,
                  Math.round(
                      (quota.used_this_month / quota.monthly_quota) * 100,
                  ),
              )
            : 0;

    return (
        <Card>
            <CardHeader className="gap-0">
                <CardTitle className="text-base">{t('ui.quota')}</CardTitle>
            </CardHeader>

            <CardContent className="space-y-3">
                <div className="flex items-end justify-between gap-4 text-sm">
                    <div>
                        <p className="text-muted-foreground">
                            {t('ui.used_this_month')}
                        </p>
                        <p className="text-lg font-semibold">
                            {quota.used_this_month}/{quota.monthly_quota}
                        </p>
                    </div>
                    <div className={isRtl ? 'text-left' : 'text-right'}>
                        <p className="text-muted-foreground">
                            {t('ui.remaining')}
                        </p>
                        <p className="text-lg font-semibold">
                            {quota.quota_remaining}
                        </p>
                    </div>
                </div>

                <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full rounded-full bg-primary transition-all"
                        style={{ width: `${percentage}%` }}
                    />
                </div>
            </CardContent>
        </Card>
    );
}
