<?php

namespace Accentinteractive\LaravelLogcleaner\Tests;

use PHPUnit\Framework\Attributes\Test;
use Accentinteractive\LaravelLogcleaner\LaravelLogcleanerServiceProvider;
use Artisan;
use Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

/**
 * Class LogCleanerTest
 */
final class LogCleanerTest extends TestCase
{

    const LOG_FOLDER_NAME = 'testlogs';
    const LOG_SUBFOLDER_NAME = 'subfolder';
    const DAILY_LOG_FILE = 'laravel.log';

    /**
     * @var string
     */
    protected $dailyLogPath;

    /**
     * @var string
     */
    protected $singleLogPath;

    /**
     * @var integer
     */
    protected $logFilesToKeep;

    /**
     * @var integer
     */
    protected $logLinesToKeep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfigs();
        $this->resetTestLogDirectory();
    }

    #[Test]
    public function itTrimsTheSingleLog(): void
    {
        $this->createSingleLog($this->logLinesToKeep + 10);

        Artisan::call('logcleaner:run');

        $numlines = count(file(Storage::path($this->getDailyLogFilePath())));
        $this->assertEquals($this->logLinesToKeep, $numlines);
    }

    #[Test]
    public function itDoesNotTrimLogsIfNotEnabled(): void
    {
        $this->createSingleLog($this->logLinesToKeep + 10);
        config(['logcleaner.trimming_enabled' => false]);

        Artisan::call('logcleaner:run');

        $numlines = count(file(Storage::path($this->getDailyLogFilePath())));
        $this->assertEquals($this->logLinesToKeep + 10, $numlines);
    }

    #[Test]
    public function itDoesNotTrimShortLogs(): void
    {
        $this->createSingleLog($this->logLinesToKeep - 1);

        Artisan::call('logcleaner:run');

        $numlines = count(file(Storage::path($this->getDailyLogFilePath())));
        $this->assertEquals($this->logLinesToKeep - 1, $numlines);
    }

    #[Test]
    public function itDeletesOldLogs(): void
    {
        $this->createDailyLogs(20, self::LOG_FOLDER_NAME . '/');
        Config::set('logging.default', 'daily');

        Artisan::call('logcleaner:run');

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $numLogFiles = count($logFiles);
        $this->assertEquals($this->logFilesToKeep, $numLogFiles);
    }

    #[Test]
    public function itDoesNotDeleteLogsIfNotEnabled(): void
    {
        $this->createDailyLogs(3, self::LOG_FOLDER_NAME . '/');
        config(['logcleaner.deleting_enabled' => false]);
        config(['logcleaner.log_files_to_keep' => 0]);

        Artisan::call('logcleaner:run');

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $numLogFiles = count($logFiles);
        $this->assertEquals(3, $numLogFiles);
    }

    #[Test]
    public function itOnlyDeletesLogFiles(): void
    {
        $logPath = $this->getLogPath();
        touch($logPath . 'textfile.txt');
        config(['logcleaner.log_files_to_keep' => 0]);

        Artisan::call('logcleaner:run');

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $numLogFiles = count($logFiles);
        $this->assertEquals(1, $numLogFiles);
    }

    #[Test]
    public function itCanExcludeFilesToDeletes(): void
    {
        $logPath = $this->getLogPath();
        touch($logPath . 'logfile.log', time() + 1);
        touch($logPath . 'logfile2.log', time() + 2);
        config(['logcleaner.log_files_to_keep' => 0]);
        config(['logcleaner.exclude' => [
            'logfile.log',
        ]]);

        Artisan::call('logcleaner:run');

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $numLogFiles = count($logFiles);
        $this->assertEquals(1, $numLogFiles);
    }

    #[Test]
    public function excludeFileNamesSupportWildcards(): void
    {
        $logPath = $this->getLogPath();
        touch($logPath . 'logfile.log', time() + 1);
        touch($logPath . 'logfile2.log', time() + 2);
        touch($logPath . 'somefile.txt', time() + 3);
        config(['logcleaner.log_files_to_keep' => 0]);
        config(['logcleaner.exclude' => [
            'logfile2*',
            '*.txt',
        ]]);

        Artisan::call('logcleaner:run');

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $numLogFiles = count($logFiles);
        $this->assertEquals(2, $numLogFiles);
    }

    #[Test]
    public function itDoesNotDeleteAnyLogsInDryRunMode(): void
    {
        $this->createSingleLog(1);
        Config::set('logging.default', 'daily');

        Artisan::call('logcleaner:run', ['-d' => true]);

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $this->assertEquals(1, count($logFiles));
    }

    #[Test]
    public function itTakesAnOptionKeeplines(): void
    {
        $this->createSingleLog(10);

        Artisan::call('logcleaner:run', ['--keeplines' => 2]);

        $numlines = count(file(Storage::path($this->getDailyLogFilePath())));
        $this->assertEquals(2, $numlines);
    }

    #[Test]
    public function itTrimsFilesInSubfolders()
    {
        $mainFolder = self::LOG_FOLDER_NAME . '/' . self::DAILY_LOG_FILE;
        $subFolder1 = $mainFolder . self::LOG_SUBFOLDER_NAME . '/' . self::DAILY_LOG_FILE;
        $subFolder2 = $mainFolder . self::LOG_SUBFOLDER_NAME . '/' . self::LOG_SUBFOLDER_NAME . '/' . self::DAILY_LOG_FILE;
        $this->createSingleLog(10, $mainFolder);
        $this->createSingleLog(10, $subFolder1);
        $this->createSingleLog(10, $subFolder2);

        Artisan::call('logcleaner:run', ['--keeplines' => 2]);

        $numlines = count(file(Storage::path($mainFolder)));
        $this->assertEquals(2, $numlines);

        $numlines = count(file(Storage::path($subFolder1)));
        $this->assertEquals(2, $numlines);

        $numlines = count(file(Storage::path($subFolder2)));
        $this->assertEquals(2, $numlines);
    }

    #[Test]
    public function itDoesNotTrimFilesInSubfoldersIfInstructed()
    {
        config(['logcleaner.process_subfolders' => false]);

        $mainLogFile = self::LOG_FOLDER_NAME . '/' . self::DAILY_LOG_FILE;
        $this->createSingleLog(10, $mainLogFile);
        $subFolderLogFile = self::LOG_FOLDER_NAME . '/' . self::LOG_SUBFOLDER_NAME . '/' . self::DAILY_LOG_FILE;
        $this->createSingleLog(10, $subFolderLogFile);

        Artisan::call('logcleaner:run', ['--keeplines' => 2]);

        $numlines = count(file(Storage::path($mainLogFile)));
        $this->assertEquals(2, $numlines);

        $numlines = count(file(Storage::path($subFolderLogFile)));
        $this->assertEquals(10, $numlines);
    }

    #[Test]
    public function itDeletesFilesInSubfolders()
    {
        $mainFolder = self::LOG_FOLDER_NAME . '/';
        $subFolder1 = $mainFolder . self::LOG_SUBFOLDER_NAME . '/';
        $subFolder2 = $mainFolder . '/' . self::LOG_SUBFOLDER_NAME . '/' . self::LOG_SUBFOLDER_NAME . '/';
        $this->createDailyLogs(3, $mainFolder);
        $this->createDailyLogs(3, $subFolder1);
        $this->createDailyLogs(3, $subFolder2);
        Artisan::call('logcleaner:run', ['--keepfiles' => 1]);

        $logFiles = Storage::files($mainFolder);
        $numLogFiles = count($logFiles);
        $this->assertEquals(1, $numLogFiles, 'Only one file should remain in the main folder');
        $this->assertStringContainsString('laravel-2.log', array_shift($logFiles), 'Only the most recent file should remain in subfolder');

        $logFiles = Storage::files($subFolder1);
        $numLogFiles = count($logFiles);
        $this->assertEquals(1, $numLogFiles, 'Only one file should remain in subfolder');
        $this->assertStringContainsString('laravel-2.log', array_shift($logFiles), 'Only the most recent file should remain in subfolder');

        $logFiles = Storage::files($subFolder2);
        $numLogFiles = count($logFiles);
        $this->assertEquals(1, $numLogFiles, 'Only one file should remain in subfolder');
        $this->assertStringContainsString('laravel-2.log', array_shift($logFiles), 'Only the most recent file should remain in subfolder');
    }

    #[Test]
    public function itDoesNotDeletesFilesInSubfoldersIfInstructed()
    {
        config(['logcleaner.process_subfolders' => false]);

        $mainLogPath = self::LOG_FOLDER_NAME . '/';
        $subFolderLogPath = self::LOG_FOLDER_NAME . '/' . self::LOG_SUBFOLDER_NAME . '/';
        $this->createDailyLogs(3, $mainLogPath);
        $this->createDailyLogs(3, $subFolderLogPath);
        Artisan::call('logcleaner:run', ['--keepfiles' => 1]);

        $logFiles = Storage::files($mainLogPath);
        $numLogFiles = count($logFiles);
        $this->assertEquals(1, $numLogFiles, 'Only one file should remain in main folder');
        $this->assertStringContainsString('laravel-2.log', array_shift($logFiles), 'Only the most recent file should remain in main folder');

        $logFiles = Storage::files($subFolderLogPath);
        $numLogFiles = count($logFiles);
        $this->assertEquals(3, $numLogFiles, 'All log files should remain in subfolder');
    }

    #[Test]
    public function itTakesAnOptionKeepfiles(): void
    {
        $this->createDailyLogs(5, self::LOG_FOLDER_NAME . '/');

        Artisan::call('logcleaner:run', ['--keepfiles' => 1]);

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $numLogFiles = count($logFiles);
        $this->assertEquals(1, $numLogFiles);
    }

    /**
     * Create the single laravel.log file, containing a given number of lines.
     *
     * @param int $numLines
     */
    protected function createSingleLog(int $numLines, string $filePath = null): void
    {
        $data = '';
        for ($i = 0; $i < $numLines; $i++) {
            $data .= 'line' . PHP_EOL;
        }

        $filePath = $filePath ?: $this->getDailyLogFilePath();

        Storage::put($filePath, $data);
    }

    /**
     * Created a given number of log files.
     *
     * @param int $numLogs
     */
    protected function createDailyLogs($numLogs = 20, string $logPath = null): void
    {
        $logPath = $logPath ?: null;

        Storage::makeDirectory($logPath);
        for ($i = 0; $i < $numLogs; $i++) {
            $time = time() + $i;
            touch(__DIR__ . '/' . $logPath . 'laravel-' . $i . '.log', $time);
        }
    }

    /**
     * Get the relative log path, as used by Laravel Storage.
     *
     * @return string
     */
    protected function getLogPath(): string
    {
        return str_replace(basename($this->dailyLogPath), '', $this->dailyLogPath);
    }

    /**
     * Get the relative path to the adily log file, as used by Laravel Storage.
     *
     * @return string
     */
    protected function getDailyLogFilePath(): string
    {
        return self::LOG_FOLDER_NAME . '/' . self::DAILY_LOG_FILE;
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelLogcleanerServiceProvider::class];
    }

    protected function setConfigs(): void
    {
        config(['logging.channels.single.path' => __DIR__ . '/testlogs/laravel.log']);
        config(['logging.channels.daily.path' => __DIR__ . '/testlogs/laravel.log']);
        config(['logcleaner.log_files_to_keep' => 2]);
        config(['logcleaner.log_lines_to_keep' => 20]);
        config(['filesystems.disks.local.root' => __DIR__]);

        $this->dailyLogPath = config('logging.channels.daily.path');
        $this->singleLogPath = config('logging.channels.single.path');
        $this->logFilesToKeep = config('logcleaner.log_files_to_keep');
        $this->logLinesToKeep = config('logcleaner.log_lines_to_keep');
    }

    protected function resetTestLogDirectory(): void
    {
        Storage::deleteDirectory(self::LOG_FOLDER_NAME);
        Storage::makeDirectory(self::LOG_FOLDER_NAME);
    }
}
