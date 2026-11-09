<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SQLite3;
use Tests\TestCase;

class PlannerBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_sqlite_backup_can_be_restored_to_a_separate_database_file(): void
    {
        $sourcePath = tempnam(sys_get_temp_dir(), 'planner-source-');
        $backupPath = tempnam(sys_get_temp_dir(), 'planner-backup-');
        $restorePath = tempnam(sys_get_temp_dir(), 'planner-restore-');
        unlink($backupPath);

        try {
            $source = new SQLite3($sourcePath);
            $source->exec('CREATE TABLE backup_probe (value TEXT NOT NULL)');
            $source->exec("INSERT INTO backup_probe (value) VALUES ('restored successfully')");
            $source->close();
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $sourcePath]);

            $this->artisan('planner:backup', ['--destination' => $backupPath])->assertSuccessful();
            $backup = new SQLite3($backupPath, SQLITE3_OPEN_READONLY);
            $restore = new SQLite3($restorePath);
            $this->assertTrue($backup->backup($restore));
            $value = $restore->querySingle('SELECT value FROM backup_probe');
            $backup->close();
            $restore->close();

            $this->assertSame('restored successfully', $value);
            $this->assertSame(0600, fileperms($backupPath) & 0777);
        } finally {
            @unlink($sourcePath);
            @unlink($backupPath);
            @unlink($restorePath);
        }
    }

    public function test_sqlite_backup_refuses_an_in_memory_database(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);

        $this->artisan('planner:backup', ['--destination' => sys_get_temp_dir().'/planner-memory-backup.sqlite'])
            ->assertFailed();
    }
}
