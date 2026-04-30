<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final class ReplayUrlBuilder
{
    private ?string $runtimeReplayRoot = null;

    public function __construct(
        private readonly ?string $replayRoot = null,
    ) {}

    public function useReplayRoot(?string $replayRoot): void
    {
        $this->runtimeReplayRoot = $replayRoot;
    }

    public function raw(CdxCapture $capture): string
    {
        return $this->build($capture, 'id_');
    }

    public function toolbar(CdxCapture $capture): string
    {
        return $this->build($capture, '');
    }

    private function build(CdxCapture $capture, string $modifier): string
    {
        $capture->assertDownloadable();

        $root = rtrim($this->runtimeReplayRoot ?? $this->replayRoot ?? (string) config('wayback-machine.replay_root', 'https://web.archive.org'), '/');

        return $root.'/web/'.$capture->timestamp.$modifier.'/'.$capture->originalUrl;
    }
}
