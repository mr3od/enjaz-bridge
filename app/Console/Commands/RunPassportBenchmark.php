<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ai\Agents\PassportExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class RunPassportBenchmark extends Command
{
    protected $signature = 'passport:benchmark-run 
        {--models= : Comma-separated model list}
        {--fixtures=tests/fixtures/passports : Fixture directory path}
        {--ground-truth=tests/fixtures/passports/ground_truth.php : Ground truth PHP file path}
        {--report-path= : Override report output path}';

    protected $description = 'Run passport extraction benchmark across fixtures and models.';

    public function handle(): int
    {
        $startedAt = microtime(true);
        $models = $this->benchmarkModels();
        $fixturesDir = base_path((string) $this->option('fixtures'));
        $groundTruthPath = base_path((string) $this->option('ground-truth'));

        if (! File::isDirectory($fixturesDir)) {
            $this->error("Fixtures directory not found: {$fixturesDir}");

            return self::FAILURE;
        }

        if (! File::exists($groundTruthPath)) {
            $this->error("Ground truth file not found: {$groundTruthPath}");

            return self::FAILURE;
        }

        /** @var array<string, array<string, string|null>> $groundTruth */
        $groundTruth = require $groundTruthPath;

        $fixtureFiles = collect(File::files($fixturesDir))
            ->map(fn (\SplFileInfo $file): string => $file->getFilename())
            ->reject(fn (string $filename): bool => $filename === 'ground_truth.php')
            ->values()
            ->all();

        foreach ($fixtureFiles as $fixtureFile) {
            $groundTruthKey = $this->resolveGroundTruthKey($fixtureFile, $groundTruth);

            if ($groundTruthKey === null) {
                $this->error("Ground truth missing for fixture: {$fixtureFile}");

                return self::FAILURE;
            }
        }

        $reportDirectory = (string) ($this->option('report-path') ?: storage_path('app/passport-benchmarks'));
        File::ensureDirectoryExists($reportDirectory);

        $rows = [];
        $totalJobs = count($models) * count($fixtureFiles);
        $successCount = 0;
        $errorCount = 0;

        $this->newLine();
        $this->info('Passport benchmark started.');
        $this->line('Models: '.implode(', ', $models));
        $this->line('Fixtures directory: '.$fixturesDir);
        $this->line('Report directory: '.$reportDirectory);
        $this->line("Total jobs: {$totalJobs}");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalJobs);
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Preparing...');
        $progressBar->start();

        app()->instance('passport.benchmark.running', true);

        try {
            foreach ($models as $model) {
                foreach ($fixtureFiles as $filename) {
                    $imagePath = $fixturesDir.'/'.$filename;
                    $groundTruthKey = $this->resolveGroundTruthKey($filename, $groundTruth);

                    if ($groundTruthKey === null) {
                        throw new \RuntimeException("Ground truth missing for fixture [{$filename}]");
                    }

                    $expected = $groundTruth[$groundTruthKey];
                    $jobStartedAt = microtime(true);

                    config()->set('ai.passport.model', $model);
                    config()->set('ai.passport.real_in_tests', true);

                    try {
                        $progressBar->setMessage("Running {$model} / {$filename}");
                        $result = app(PassportExtractor::class)->extractFromImagePath($imagePath);
                        $actual = (array) ($result['extracted'] ?? []);
                        $score = $this->calculateAccuracy($expected, $actual);

                        $payload = [
                            'success' => true,
                            'model' => $model,
                            'filename' => $filename,
                            'accuracy' => $score['accuracy'],
                            'recorded_at' => now()->toISOString(),
                            'elapsed_ms' => (int) ((microtime(true) - $jobStartedAt) * 1000),
                            'result' => $result,
                            'fields' => $score['rows'],
                        ];

                        $reportPath = $this->writeReport($reportDirectory, $model, $filename, $payload);

                        $rows[] = [$model, $filename, "{$score['accuracy']}%", 'OK'];
                        $successCount++;
                        $progressBar->advance();
                        $this->newLine();
                        $this->info("OK {$model} {$filename} {$score['accuracy']}% ({$payload['elapsed_ms']} ms)");
                        $this->line("Report: {$reportPath}");
                    } catch (Throwable $exception) {
                        $payload = [
                            'success' => false,
                            'model' => $model,
                            'filename' => $filename,
                            'recorded_at' => now()->toISOString(),
                            'elapsed_ms' => (int) ((microtime(true) - $jobStartedAt) * 1000),
                            'error' => $exception->getMessage(),
                        ];

                        $reportPath = $this->writeReport($reportDirectory, $model, $filename, $payload);

                        $rows[] = [$model, $filename, '-', 'ERROR'];
                        $errorCount++;
                        $progressBar->advance();
                        $this->newLine();
                        $this->error("ERROR {$model} {$filename}: {$exception->getMessage()}");
                        $this->line("Report: {$reportPath}");
                    }
                }
            }
        } finally {
            app()->forgetInstance('passport.benchmark.running');
            $progressBar->finish();
        }

        $this->newLine();
        $this->newLine();
        $this->table(['Model', 'Fixture', 'Accuracy', 'Status'], $rows);
        $this->line('Completed in '.(int) ((microtime(true) - $startedAt) * 1000).' ms');
        $this->line("Successful jobs: {$successCount}");
        $this->line("Failed jobs: {$errorCount}");
        $this->line("Reports saved in: {$reportDirectory}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function benchmarkModels(): array
    {
        $optionModels = trim((string) $this->option('models'));

        if ($optionModels !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $optionModels))));
        }

        $envModels = (string) env(
            'PASSPORT_TEST_MODELS',
            'openai-responses/gpt-5-nano,openai-responses/gpt-4.1-nano,openai/gpt-4o-mini,minimaxi/MiniMax-M2.5'
        );

        return array_values(array_filter(array_map('trim', explode(',', $envModels))));
    }

    /**
     * @param  array<string, array<string, string|null>>  $groundTruth
     */
    private function resolveGroundTruthKey(string $fixtureFilename, array $groundTruth): ?string
    {
        $candidates = [$fixtureFilename];

        if (str_contains($fixtureFilename, '_final.')) {
            $candidates[] = str_replace('_final.', '.', $fixtureFilename);
        }

        $expanded = [];

        foreach ($candidates as $candidate) {
            $expanded[] = $candidate;
            $expanded[] = preg_replace('/\.(jpg)$/i', '.jpeg', $candidate) ?? $candidate;
            $expanded[] = preg_replace('/\.(jpeg)$/i', '.jpg', $candidate) ?? $candidate;
        }

        foreach (array_unique($expanded) as $candidate) {
            if (array_key_exists($candidate, $groundTruth)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|null>  $expected
     * @param  array<string, mixed>  $actual
     * @return array{accuracy: float, rows: array<int, array<string, mixed>>}
     */
    private function calculateAccuracy(array $expected, array $actual): array
    {
        $exactFields = ['PassportNumber', 'CountryCode', 'DateOfBirth', 'DateOfIssue', 'DateOfExpiry', 'Sex', 'MrzLine1', 'MrzLine2'];
        $fuzzyFields = ['SurnameEn', 'GivenNamesEn', 'ProfessionEn', 'IssuingAuthorityEn', 'PlaceOfBirthEn'];
        $arabicFields = ['SurnameAr', 'GivenNamesAr'];

        $rows = [];
        $total = 0;
        $matched = 0;

        foreach ($exactFields as $field) {
            $total++;

            if (in_array($field, ['MrzLine1', 'MrzLine2'], true)) {
                $exp = $this->normalizeMrz($expected[$field] ?? null);
                $act = $this->normalizeMrz($actual[$field] ?? null);
            } else {
                $exp = $this->normalizeValue($expected[$field] ?? null);
                $act = $this->normalizeValue($actual[$field] ?? null);
            }

            $ok = $exp === $act;
            $rows[] = compact('field', 'exp', 'act', 'ok') + ['type' => 'exact'];
            if ($ok) {
                $matched++;
            }
        }

        foreach ($fuzzyFields as $field) {
            $total++;
            $exp = $this->normalizeText($expected[$field] ?? null);
            $act = $this->normalizeText($actual[$field] ?? null);
            $ok = $exp === $act;
            $rows[] = compact('field', 'exp', 'act', 'ok') + ['type' => 'fuzzy'];
            if ($ok) {
                $matched++;
            }
        }

        foreach ($arabicFields as $field) {
            $total++;
            $exp = $this->normalizeArabic($expected[$field] ?? null);
            $act = $this->normalizeArabic($actual[$field] ?? null);
            $ok = $exp === $act;
            $rows[] = compact('field', 'exp', 'act', 'ok') + ['type' => 'arabic'];
            if ($ok) {
                $matched++;
            }
        }

        $accuracy = $total > 0 ? round(($matched / $total) * 100, 1) : 0.0;

        return ['accuracy' => $accuracy, 'rows' => $rows];
    }

    private function normalizeValue(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    private function normalizeText(mixed $value): ?string
    {
        $normalized = $this->normalizeValue($value);

        if ($normalized === null) {
            return null;
        }

        return mb_strtoupper((string) preg_replace('/\s+/', ' ', $normalized));
    }

    private function normalizeMrz(mixed $value): ?string
    {
        $normalized = $this->normalizeValue($value);

        if ($normalized === null) {
            return null;
        }

        return mb_strtoupper((string) preg_replace('/\s+/', '', $normalized));
    }

    private function normalizeArabic(mixed $value): ?string
    {
        $normalized = $this->normalizeValue($value);

        if ($normalized === null) {
            return null;
        }

        return (string) preg_replace('/\s+/', ' ', $normalized);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeReport(string $directory, string $model, string $filename, array $payload): string
    {
        $safeModel = str_replace('/', '_', $model);
        $safeFile = pathinfo($filename, PATHINFO_FILENAME);
        $timestamp = now()->format('Ymd_His_u');
        $reportPath = "{$directory}/{$timestamp}__{$safeModel}__{$safeFile}.json";

        File::put($reportPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $reportPath;
    }
}
