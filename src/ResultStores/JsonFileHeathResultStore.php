<?php

namespace Spatie\Health\ResultStores;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Health\OhDear\CheckResult;
use Spatie\Health\OhDear\Run;
use Spatie\Health\Support\Result;

class JsonFileHeathResultStore implements ResultStore
{
    protected Filesystem $disk;
    protected string $path;

    public function __construct(string $diskName, string $path)
    {
        $this->disk = Storage::disk($diskName);

        $this->path = $path;
    }

    /** @param Collection<int, \Spatie\Health\Support\Result> */
    public function save(Collection $checkResults): void
    {
        $run = new Run(now());

        $checkResults
            ->map(function (Result $result) {
                return new CheckResult(
                    name: $result->check->name(),
                    message: $result->getMessage(),
                    status: $result->status->value,
                    meta: $result->meta,
                );
            })
            ->each(fn (CheckResult $result) => $run->addCheck($result));

        $contents = $run->toJson();

        $this->disk->write($this->path, $contents);
    }
}