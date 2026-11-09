<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class PlannerBackup extends Command
{
    protected $signature = 'planner:backup {--destination=} {--keep-days=14}';

    protected $description = 'Create a database backup and prune backups older than the retention period';

    public function handle(): int
    {
        $connection = config('database.default');
        $destination = $this->option('destination') ?: storage_path('app/backups/daily-planner-'.now('UTC')->format('Ymd-His').($connection === 'sqlite' ? '.sqlite' : '.sql'));
        if (file_exists($destination)) {
            $this->error('The backup destination already exists. Choose a new path; existing files are never overwritten.');

            return self::FAILURE;
        }
        $directory = dirname($destination);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            $this->error('Could not create the backup directory.');

            return self::FAILURE;
        }

        try {
            match ($connection) {
                'sqlite' => $this->backupSqlite($destination),
                'mysql' => $this->backupMysql($destination),
                'pgsql' => $this->backupPostgres($destination),
                default => throw new RuntimeException("Backups are not configured for database connection [{$connection}]."),
            };
            chmod($destination, 0600);
        } catch (Throwable $exception) {
            if (is_file($destination)) {
                unlink($destination);
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->pruneOldBackups((int) $this->option('keep-days'));
        $this->info('Database backup created: '.$destination);

        return self::SUCCESS;
    }

    private function backupSqlite(string $destination): void
    {
        if (! class_exists(\SQLite3::class)) {
            throw new RuntimeException('The PHP SQLite3 extension is required for SQLite backups.');
        }

        $source = config('database.connections.sqlite.database');
        if (! is_string($source) || $source === ':memory:' || ! is_file($source)) {
            throw new RuntimeException('SQLite backup needs an existing database file; in-memory databases cannot be backed up.');
        }

        $sourceDb = new \SQLite3($source, SQLITE3_OPEN_READONLY);
        $backupDb = new \SQLite3($destination);
        try {
            if (! $sourceDb->backup($backupDb)) {
                throw new RuntimeException('SQLite could not create the backup copy.');
            }
        } finally {
            $sourceDb->close();
            $backupDb->close();
        }
    }

    private function backupMysql(string $destination): void
    {
        $database = config('database.connections.mysql');
        $binary = config('backup.mysql_dump_binary', 'mysqldump');
        $credentialFile = tempnam(storage_path('framework'), 'planner-db-');
        if ($credentialFile === false) {
            throw new RuntimeException('Could not create a temporary MySQL credentials file.');
        }

        $escape = fn (string $value): string => str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '', ''], $value);
        file_put_contents($credentialFile, "[client]\nuser=\"{$escape((string) $database['username'])}\"\npassword=\"{$escape((string) $database['password'])}\"\nhost=\"{$escape((string) $database['host'])}\"\nport=\"{$escape((string) ($database['port'] ?? 3306))}\"\n");
        chmod($credentialFile, 0600);

        try {
            $process = new Process([
                $binary,
                '--defaults-extra-file='.$credentialFile,
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                '--result-file='.$destination,
                (string) $database['database'],
            ]);
            $process->setTimeout(300);
            $process->run();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('MySQL dump failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
            }
        } finally {
            unlink($credentialFile);
        }
    }

    private function backupPostgres(string $destination): void
    {
        $database = config('database.connections.pgsql');
        $binary = config('backup.postgres_dump_binary', 'pg_dump');
        $passFile = tempnam(storage_path('framework'), 'planner-pg-');
        if ($passFile === false) {
            throw new RuntimeException('Could not create a temporary PostgreSQL credentials file.');
        }

        $escape = fn (string $value): string => str_replace(['\\', ':', "\n", "\r"], ['\\\\', '\\:', '', ''], $value);
        file_put_contents($passFile, implode(':', [
            $escape((string) ($database['host'] ?? 'localhost')),
            $escape((string) ($database['port'] ?? 5432)),
            $escape((string) $database['database']),
            $escape((string) $database['username']),
            $escape((string) ($database['password'] ?? '')),
        ])."\n");
        chmod($passFile, 0600);

        try {
            $process = new Process([
                $binary,
                '--no-owner',
                '--no-privileges',
                '--file='.$destination,
                '--host='.(string) ($database['host'] ?? 'localhost'),
                '--port='.(string) ($database['port'] ?? 5432),
                '--username='.(string) $database['username'],
                (string) $database['database'],
            ], null, ['PGPASSFILE' => $passFile]);
            $process->setTimeout(300);
            $process->run();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('PostgreSQL dump failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
            }
        } finally {
            unlink($passFile);
        }
    }

    private function pruneOldBackups(int $keepDays): void
    {
        $cutoff = now('UTC')->subDays(max(1, $keepDays))->getTimestamp();
        foreach (glob(storage_path('app/backups/daily-planner-*')) ?: [] as $backup) {
            if (is_file($backup) && filemtime($backup) < $cutoff) {
                unlink($backup);
            }
        }
    }
}
