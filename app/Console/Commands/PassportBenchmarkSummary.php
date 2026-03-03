<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class PassportBenchmarkSummary extends Command
{
    protected $signature = 'passport:benchmark-summary {--path= : Override report directory path}';

    protected $description = 'Summarize passport benchmark reports from JSON files.';

    public function handle(): int
    {
        $directory = (string) ($this->option('path') ?: storage_path('app/passport-benchmarks'));

        if (! File::isDirectory($directory)) {
            $this->error("Report directory not found: {$directory}");

            return self::FAILURE;
        }

        $files = collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file): bool => str_ends_with($file->getFilename(), '.json'))
            ->values();

        if ($files->isEmpty()) {
            $this->error("No JSON reports found in: {$directory}");

            return self::FAILURE;
        }

        $reports = $files
            ->map(function (\SplFileInfo $file): ?array {
                /** @var array<string, mixed>|null $decoded */
                $decoded = json_decode(File::get($file->getPathname()), true);

                return is_array($decoded) ? $decoded : null;
            })
            ->filter()
            ->values();

        if ($reports->isEmpty()) {
            $this->error('No readable report payloads found.');

            return self::FAILURE;
        }

        $failedRuns = $reports
            ->filter(fn (array $report): bool => (bool) ($report['success'] ?? true) === false)
            ->count();

        $successfulReports = $reports
            ->filter(fn (array $report): bool => (bool) ($report['success'] ?? true) === true)
            ->values();

        if ($successfulReports->isEmpty()) {
            $this->error('No successful benchmark reports found.');

            return self::FAILURE;
        }

        /** @var Collection<string, Collection<int, array<string, mixed>>> $grouped */
        $grouped = $successfulReports->groupBy(
            fn (array $report): string => (string) ($report['model'] ?? 'unknown')
        );

        $rows = $grouped->map(function (Collection $modelReports, string $model): array {
            $avgAccuracy = round(
                $modelReports
                    ->map(fn (array $report): float => (float) ($report['accuracy'] ?? 0))
                    ->avg() ?? 0,
                1
            );

            $icon = $avgAccuracy >= 95.0 ? 'OK' : ($avgAccuracy >= 80.0 ? 'WARN' : 'BAD');

            return [
                'model' => $model,
                'runs' => $modelReports->count(),
                'avg' => "{$avgAccuracy}%",
                'status' => $icon,
            ];
        })->sortByDesc(
            fn (array $row): float => (float) rtrim($row['avg'], '%')
        )->values();

        $this->table(
            ['Model', 'Runs', 'Avg Accuracy', 'Status'],
            $rows->map(fn (array $row): array => [$row['model'], $row['runs'], $row['avg'], $row['status']])->all()
        );

        $this->line("Report directory: {$directory}");
        $this->line("Successful runs: {$successfulReports->count()}");
        $this->line("Failed runs: {$failedRuns}");

        return self::SUCCESS;
    }
}
