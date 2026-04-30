<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final readonly class WaybackMirror
{
    public function __construct(
        private WaybackClient $client,
        private WaybackDownloader $downloader,
        private OutputPathBuilder $paths,
    ) {}

    /**
     * @return list<DownloadResult>
     */
    public function mirror(CaptureScope $scope, CdxQuery $query, string $output, WaybackOptions $options): array
    {
        $captures = $this->client->captures($scope, $query, $options);
        $results = [];

        foreach ($captures as $capture) {
            $results[] = $this->downloader->download(
                $capture,
                $this->paths->capturePath($scope, $capture, $output),
                $options,
            );
        }

        return $results;
    }
}
