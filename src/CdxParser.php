<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use RuntimeException;

final class CdxParser
{
    /**
     * @return list<CdxCapture>
     */
    public function parse(string $json): array
    {
        return $this->parsePage($json)->captures;
    }

    public function parsePage(string $json): CdxPage
    {
        $rows = json_decode($json, true);

        if (! is_array($rows)) {
            throw new RuntimeException('CDX response is not valid JSON.');
        }

        if ($rows === []) {
            return new CdxPage([], null);
        }

        $header = array_shift($rows);

        if (! is_array($header)) {
            throw new RuntimeException('CDX response is missing a header row.');
        }

        if ($header === ['resumeKey']) {
            $resumeKey = null;
            $first = $rows[0] ?? null;

            if (is_array($first) && is_string($first[0] ?? null)) {
                $resumeKey = $first[0];
            }

            return new CdxPage([], $resumeKey);
        }

        $resumeKey = null;

        if ($rows !== []) {
            $last = end($rows);

            if (is_array($last) && count($last) === 1 && is_string($last[0] ?? null)) {
                $resumeKey = $last[0];
                array_pop($rows);
            }
        }

        $captures = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($row === []) {
                continue;
            }

            $mapped = [];

            foreach ($header as $index => $field) {
                if (is_string($field) && array_key_exists($index, $row)) {
                    $mapped[$field] = $row[$index];
                }
            }

            $captures[] = CdxCapture::fromRow($mapped);
        }

        return new CdxPage($captures, $resumeKey);
    }
}
