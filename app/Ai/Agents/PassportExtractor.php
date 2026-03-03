<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Files\Image;
use RuntimeException;

use function Laravel\Ai\agent;

class PassportExtractor
{
    /**
     * @var array<int, string>
     */
    private const OUTPUT_FIELDS = [
        'PassportNumber',
        'CountryCode',
        'MrzLine1',
        'MrzLine2',
        'SurnameAr',
        'GivenNamesAr',
        'SurnameEn',
        'GivenNamesEn',
        'DateOfBirth',
        'PlaceOfBirthAr',
        'PlaceOfBirthEn',
        'Sex',
        'DateOfIssue',
        'DateOfExpiry',
        'ProfessionAr',
        'ProfessionEn',
        'IssuingAuthorityAr',
        'IssuingAuthorityEn',
    ];

    /**
     * @return array{raw: array<string, mixed>, extracted: array<string, mixed>, model: string}
     */
    public function extractFromImagePath(string $imagePath): array
    {
        $allowRealExtractionInTests = (bool) config('ai.passport.real_in_tests', false)
            && app()->bound('passport.benchmark.running');

        if (app()->environment('testing') && ! $allowRealExtractionInTests) {
            return $this->fakeResponse($imagePath);
        }

        if (! is_file($imagePath)) {
            throw new RuntimeException('Passport image file was not found.');
        }

        $provider = (string) config('ai.default', 'openai');
        $model = (string) config('ai.passport.model', 'openai-responses/gpt-5-mini');

        $providerConfig = (array) config("ai.providers.$provider", []);
        $providerKey = Arr::get($providerConfig, 'key') ?? Arr::get($providerConfig, 'api_key');

        if (! in_array($provider, ['ollama'], true) && blank($providerKey)) {
            throw new RuntimeException("AI provider [$provider] is not configured. Add its API key in .env before extraction.");
        }

        if ($provider === 'requesty') {
            $response = $this->promptRequestyChat($imagePath, $model, (string) $providerKey);
            $structured = $this->extractJsonPayload((string) $response['text']);

            return $this->mapResponse($imagePath, $model, $response, $structured);
        }

        try {
            $response = $this->promptStructured($imagePath, $provider, $model);
            $structured = method_exists($response, 'toArray')
                ? (array) $response->toArray()
                : [];
        } catch (AiException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'invalid request format')) {
                throw $exception;
            }

            Log::warning('passport.extractor.structured_fallback', [
                'provider' => $provider,
                'model' => $model,
                'error' => $exception->getMessage(),
            ]);

            $response = $this->promptJson($imagePath, $provider, $model);
            $structured = $this->extractJsonPayload($response->text);
        }

        return $this->mapResponse($imagePath, $model, [
            'provider' => (string) $response->meta->provider,
            'model' => (string) $response->meta->model,
            'text' => (string) $response->text,
        ], $structured);
    }

    private function getBaseInstructions(): string
    {
        return <<<'TEXT'
You extract fields from a single passport image.
The image may show:
- A single passport data page
- A two-page spread (open passport booklet)
- A passport on an A4 scanned sheet with background/margins

- Extract surname, given names, profession, place of birth, and issuing authority the arabic and english versions.
- Extract date of birth, sex, date of issue and date of expiry from english fields only.
- Extract the 2 MRZ lines.
Rules:
- Return only factual values visible in the image.
- Do not invent or infer missing values.
- Keep Arabic fields in Arabic script exactly as seen.
- Keep English fields in uppercase as shown when possible.
- For dates, use strictly DD/MM/YYYY format. If uncertain, return null.
- For Sex, return only "M" or "F" when confidently visible, otherwise null.
TEXT;
    }

    private function promptStructured(string $imagePath, string $provider, string $model): mixed
    {
        return agent(
            instructions: $this->getBaseInstructions(),
            schema: fn (JsonSchema $schema): array => [
                'PassportNumber' => $schema->string()->nullable(),
                'CountryCode' => $schema->string()->nullable(),
                'MrzLine1' => $schema->string()->nullable(),
                'MrzLine2' => $schema->string()->nullable(),
                'SurnameAr' => $schema->string()->nullable(),
                'GivenNamesAr' => $schema->string()->nullable(),
                'SurnameEn' => $schema->string()->nullable(),
                'GivenNamesEn' => $schema->string()->nullable(),
                'DateOfBirth' => $schema->string()->nullable(),
                'PlaceOfBirthAr' => $schema->string()->nullable(),
                'PlaceOfBirthEn' => $schema->string()->nullable(),
                'Sex' => $schema->string()->nullable(),
                'DateOfIssue' => $schema->string()->nullable(),
                'DateOfExpiry' => $schema->string()->nullable(),
                'ProfessionAr' => $schema->string()->nullable(),
                'ProfessionEn' => $schema->string()->nullable(),
                'IssuingAuthorityAr' => $schema->string()->nullable(),
                'IssuingAuthorityEn' => $schema->string()->nullable(),
            ],
        )->prompt(
            prompt: 'Extract passport fields from this image and return the structured output.',
            attachments: [Image::fromPath($imagePath)],
            provider: $provider,
            model: $model,
        );
    }

    private function promptJson(string $imagePath, string $provider, string $model): mixed
    {
        $fieldList = implode(', ', self::OUTPUT_FIELDS);

        $instructions = $this->getBaseInstructions()."\n".<<<'TEXT'
- Return strict JSON object only. No markdown. No extra keys.
- If a value is not visible, set it to null.
TEXT;

        return agent(
            instructions: $instructions,
        )->prompt(
            prompt: "Extract these fields and return only JSON with exactly these keys: {$fieldList}.",
            attachments: [Image::fromPath($imagePath)],
            provider: $provider,
            model: $model,
        );
    }

    /**
     * @return array{provider: string, model: string, text: string}
     */
    private function promptRequestyChat(string $imagePath, string $model, string $apiKey): array
    {
        $imageBytes = file_get_contents($imagePath);

        if ($imageBytes === false) {
            throw new RuntimeException('Unable to read passport image file.');
        }

        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
        $dataUri = 'data:'.$mimeType.';base64,'.base64_encode($imageBytes);
        $fieldList = implode(', ', self::OUTPUT_FIELDS);
        $baseUrl = rtrim((string) config('ai.providers.requesty.url', 'https://router.requesty.ai/v1'), '/');

        $instructions = $this->getBaseInstructions()."\n".<<<'TEXT'
- Return strict JSON object only. No markdown. No extra keys.
- If a value is not visible, set it to null.
TEXT;

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(180)
            ->withHeaders([
                'HTTP-Referer' => (string) config('ai.providers.requesty.headers.HTTP-Referer', config('app.url')),
                'X-Title' => (string) config('ai.providers.requesty.headers.X-Title', config('app.name')),
            ])
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'temperature' => 0,
                'messages' => [[
                    'role' => 'system',
                    'content' => $instructions,
                ], [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'text',
                        'text' => "Extract these fields and return only JSON with exactly these keys: {$fieldList}.",
                    ], [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $dataUri,
                        ],
                    ],
                    ],
                ],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Requesty chat completion failed [%s]: %s',
                $response->status(),
                (string) $response->body()
            ));
        }

        $payload = (array) $response->json();
        $content = data_get($payload, 'choices.0.message.content');

        if (is_array($content)) {
            $content = collect($content)
                ->map(fn (mixed $item): string => (string) (data_get($item, 'text') ?? data_get($item, 'content') ?? ''))
                ->implode("\n");
        }

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Requesty response did not contain model content.');
        }

        return [
            'provider' => 'requesty',
            'model' => (string) (data_get($payload, 'model') ?? $model),
            'text' => $content,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractJsonPayload(string $text): array
    {
        $trimmed = trim($text);

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $fragment = substr($trimmed, $start, $end - $start + 1);
            $decoded = json_decode($fragment, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('AI response is not valid JSON for passport extraction.');
    }

    /**
     * @param  array{provider: string, model: string, text: string}  $response
     * @param  array<string, mixed>  $structured
     * @return array{raw: array<string, mixed>, extracted: array<string, mixed>, model: string}
     */
    private function mapResponse(string $imagePath, string $model, array $response, array $structured): array
    {
        $extracted = [];

        foreach (self::OUTPUT_FIELDS as $field) {
            $extracted[$field] = $structured[$field] ?? null;
        }

        return [
            'raw' => [
                'source' => basename($imagePath),
                'provider' => $response['provider'],
                'model' => $response['model'],
                'text' => $response['text'],
                'structured' => $structured,
            ],
            'extracted' => $extracted,
            'model' => ! empty($response['model']) ? $response['model'] : $model,
        ];
    }

    /**
     * @return array{raw: array<string, mixed>, extracted: array<string, mixed>, model: string}
     */
    private function fakeResponse(string $imagePath): array
    {
        $extracted = [
            'PassportNumber' => 'A12345678',
            'CountryCode' => 'YEM',
            'MrzLine1' => 'P<YEMALHASHMI<<MOHAMMED<<<<<<<<<<<<',
            'MrzLine2' => 'A12345678<3YEM9001019M3001012<<<<<<',
            'SurnameAr' => 'الهاشمي',
            'GivenNamesAr' => 'محمد',
            'SurnameEn' => 'ALHASHMI',
            'GivenNamesEn' => 'MOHAMMED',
            'DateOfBirth' => '01/01/1990',
            'PlaceOfBirthAr' => 'صنعاء',
            'PlaceOfBirthEn' => 'Sanaa',
            'Sex' => 'M',
            'DateOfIssue' => now()->subYears(3)->format('d/m/Y'),
            'DateOfExpiry' => now()->addYears(2)->format('d/m/Y'),
            'ProfessionAr' => 'موظف',
            'ProfessionEn' => 'Employee',
            'IssuingAuthorityAr' => 'مصلحة الهجرة',
            'IssuingAuthorityEn' => 'Immigration Authority',
        ];

        return [
            'raw' => [
                'source' => basename($imagePath),
                'extracted' => $extracted,
            ],
            'extracted' => $extracted,
            'model' => 'baseline-extractor-v1',
        ];
    }
}
