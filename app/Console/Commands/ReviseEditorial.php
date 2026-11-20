<?php

namespace App\Console\Commands;

use App\Support\EditorialRevision;
use Illuminate\Console\Command;
use Throwable;

class ReviseEditorial extends Command
{
    protected $signature = 'editorial:revise {manifest} {--apply : Apply reviewed changes} {--backup= : New private rollback manifest, required when applying}';

    protected $description = 'Preview or atomically apply reviewed editorial revisions without changing URLs';

    public function handle(EditorialRevision $revision): int
    {
        try {
            if ($this->option('apply') && ! $this->option('backup')) {
                throw new \RuntimeException('--apply requires --backup with a new private file path.');
            }
            $data = json_decode(file_get_contents($this->argument('manifest')), true, flags: JSON_THROW_ON_ERROR);
            $count = $revision->run($data, $this->option('apply') ? $this->option('backup') : null);
            $this->info($count.($this->option('apply') ? ' articles revised.' : ' articles would change; database unchanged.'));

            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
    }
}
