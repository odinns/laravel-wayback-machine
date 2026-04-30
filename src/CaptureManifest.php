<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final readonly class CaptureManifest
{
    /**
     * @param  list<CdxCapture>  $captures
     */
    public function __construct(
        public CaptureScope $scope,
        public array $captures,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(ReplayUrlBuilder $urls): array
    {
        return [
            'scope' => [
                'value' => $this->scope->value,
                'match' => $this->scope->match,
            ],
            'generated_at' => now()->toIso8601String(),
            'captures' => array_map(
                fn (CdxCapture $capture): array => $capture->toManifestArray($urls),
                $this->captures,
            ),
        ];
    }
}
