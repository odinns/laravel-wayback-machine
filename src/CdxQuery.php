<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final readonly class CdxQuery
{
    /**
     * @param  list<int>  $statuses
     * @param  list<string>  $mimeTypes
     * @param  list<string>  $includePatterns
     * @param  list<string>  $excludePatterns
     * @param  list<string>  $fields
     */
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public array $statuses = [],
        public array $mimeTypes = [],
        public array $includePatterns = [],
        public array $excludePatterns = [],
        public ?string $collapse = null,
        public ?int $limit = null,
        public ?int $pageLimit = null,
        public array $fields = CdxCapture::DEFAULT_FIELDS,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function parametersFor(CaptureScope $scope, int $page = 0, ?string $resumeKey = null): array
    {
        $parameters = [
            'url' => $scope->cdxUrl(),
            'matchType' => $scope->cdxMatchType(),
            'output' => 'json',
            'fl' => implode(',', $this->fields),
            'showResumeKey' => 'true',
        ];

        if ($resumeKey !== null) {
            $parameters['resumeKey'] = $resumeKey;
        } else {
            $parameters['page'] = $page;
        }

        if ($this->from !== null) {
            $parameters['from'] = $this->from;
        }

        if ($this->to !== null) {
            $parameters['to'] = $this->to;
        }

        if ($this->limit !== null) {
            $parameters['limit'] = $this->limit;
        }

        if ($this->collapse !== null) {
            $parameters['collapse'] = $this->collapse;
        }

        $filters = [];

        foreach ($this->statuses as $status) {
            $filters[] = 'statuscode:'.$status;
        }

        foreach ($this->mimeTypes as $mimeType) {
            $filters[] = 'mimetype:'.$mimeType;
        }

        foreach ($this->includePatterns as $pattern) {
            $filters[] = 'original:'.$pattern;
        }

        foreach ($this->excludePatterns as $pattern) {
            $filters[] = '!original:'.$pattern;
        }

        if ($filters !== []) {
            $parameters['filter'] = $filters;
        }

        return $parameters;
    }
}
