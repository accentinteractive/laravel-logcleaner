<?php

namespace Accentinteractive\LaravelLogcleaner\Tests;

use Accentinteractive\LaravelLogcleaner\LaravelLogcleanerServiceProvider;
use Artisan;
use Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

/**
 * Class LogCleanerTest
 */
class LogCleanerTest extends TestCase
{

    const LOG_FOLDER_NAME = 'testlogs';
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

    /** @test */
    public function itTrimsTheSingleLog(): void
    {
        $this->createSingleLog($this->logLinesToKeep + 10);

        Artisan::call('logcleaner:run');

        $numlines = count(file(Storage::path($this->getDailyLogFilePath())));
        $this->assertEquals($this->logLinesToKeep, $numlines);
    }

    /** @test */
    public function itDoesNotTrimLogsIfNotEnabled(): void
    {
        $this->createSingleLog($this->logLinesToKeep + 10);
        config(['logcleaner.trimming_enabled' => false]);

        Artisan::call('logcleaner:run');

        $numlines = count(file(Storage::path($this->getDailyLogFilePath())));
        $this->assertEquals($this->logLinesToKeep + 10, $numlines);
    }

    /** @test */
    public function itDoesNotTrimShortLogs(): void
    {
        $this->createSingleLog($this->logLinesToKeep - 1);

        Artisan::call('logcleaner:run');

        $numlines = count(file(Storage::path($this->getDailyLogFilePath())));
        $this->assertEquals($this->logLinesToKeep - 1, $numlines);
    }

    /** @test */
    public function itDeletesOldLogs(): void
    {
        $this->createDailyLogs(20);
        Config::set('logging.default', 'daily');

        Artisan::call('logcleaner:run');

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $numLogFiles = count($logFiles);
        $this->assertEquals($this->logFilesToKeep, $numLogFiles);
    }

    /** @test */
    public function itDoesNotDeleteLogsIfNotEnabled(): void
    {
        $this->createDailyLogs(3);
        config(['logcleaner.deleting_enabled' => false]);
        config(['logcleaner.log_files_to_keep' => 0]);

        Artisan::call('logcleaner:run');

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $numLogFiles = count($logFiles);
        $this->assertEquals(3, $numLogFiles);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function itDoesNotDeleteAnyLogsInDryRunMode(): void
    {
        $this->createSingleLog(1);
        Config::set('logging.default', 'daily');

        Artisan::call('logcleaner:run', ['-d' => true]);

        $logFiles = Storage::files(self::LOG_FOLDER_NAME);
        $this->assertEquals(1, count($logFiles));
    }

    /**
     * Create the single laravel.log file, containing a given number of lines.
     *
     * @param int $numLines
     */
    protected function createSingleLog(int $numLines): void
    {
        $data = '';
        for ($i = 0; $i < $numLines; $i++) {
            $data .= 'line' . PHP_EOL;
        }

        Storage::put($this->getDailyLogFilePath(), $data);
    }

    /**
     * Created a given number of log files.
     *
     * @param int $numLogs
     */
    protected function createDailyLogs($numLogs = 20): void
    {
        $logPath = $this->getLogPath();
        for ($i = 0; $i < $numLogs; $i++) {
            $time = time() + $i;
            touch($logPath . 'laravel-' . $i . '.log', $time);
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
