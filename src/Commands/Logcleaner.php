<?php

namespace Accentinteractive\LaravelLogcleaner\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Logcleaner extends Command
{

    const LOG_FILE_EXTENSION = 'log';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logcleaner:run {--d|dry-run : Run without actually cleaning any logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes and truncates old logs. Only "single" and "daily" are supported.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->option('dry-run') == true) {
            $this->comment('This is a dry-run. We are not deleting or truncating any actual logs.');
        }

        $logPath = config('logging.channels.single.path');
        $logPath = str_replace(basename($logPath), '', $logPath);

        $this->comment('Deleting old log files...');
        $msg = $this->deleteOldLogFiles($this->getLaravelLogFilesCollection($logPath));
        $this->line($msg);

        $this->comment('Trimming log files...');
        $msg = $this->trimLogFiles($this->getLaravelLogFilesCollection($logPath));
        $this->line($msg);
    }

    /**
     * Trim each log file to a given number of lines.
     *
     * @param Collection $logFiles
     *
     * @return string
     */
    protected function trimLogFiles(Collection $logFiles): string
    {
        if ($this->option('dry-run') == true) {
            return 'Would trim '.$logFiles->count().' logfiles';
        }

        $msg = '';
        foreach ($logFiles as $logFile) {
            /* @var \Symfony\Component\Finder\SplFileInfo $logFile */
            $msg .= $this->trimFile($logFile->getRealPath()) . PHP_EOL;
        }

        return $msg;
    }

    /**
     * Delete logfiles, except a given amount of newest file.
     * The number of logfiles to keep is set in config.
     *
     * @param Collection $logFiles
     *
     * @return string
     */
    protected function deleteOldLogFiles(Collection $logFiles): string
    {
        if ($this->option('dry-run') == true) {
            return 'Found logs ' . json_encode($logFiles->toArray());
        }

        $msg = '';
        $logFilesToDelete = $logFiles->splice(config('logcleaner.log_files_to_keep'));
        foreach ($logFilesToDelete as $logFile) {
            /* @var \Symfony\Component\Finder\SplFileInfo $logFile */
            $msg .= $this->deleteFile($logFile->getRealPath()) . PHP_EOL;
        }

        return $msg;
    }

    /**
     * Return a collection of all the files in the
     * log directory that have the .log extension.
     *
     * @param string $logPath
     *
     * @return Collection
     */
    protected function getLaravelLogFilesCollection(string $logPath)
    {
        $fileNames = File::files($logPath);
        $fileNames = collect($fileNames)->filter(function ($item) {
            return $item->getExtension() == self::LOG_FILE_EXTENSION;
        });

        $logFiles = collect([]);
        foreach ($fileNames as $file) {
            $logFiles->put($file->getMTime(), $file);
        }

        return $logFiles->sortDesc();
    }

    /**
     * Trim a log file to a given number of lines.
     *
     * @param string $logPath
     *
     * @return string
     */
    protected function trimFile(string $logPath): string
    {
        $logLinesToKeep = config('logcleaner.log_lines_to_keep');

        if ( ! file_exists($logPath) || ! is_file($logPath)) {
            return 'File ' . basename($logPath) . ' could not be found';
        }

        if ($this->option('dry-run') == true) {
            return basename($logPath) . ' would be trimmed to ' . $logLinesToKeep . ' lines.';
        }

        exec('echo "$(tail -' . $logLinesToKeep . ' ' . $logPath . ')" > ' . $logPath);

        return basename($logPath) . ' was trimmed to ' . $logLinesToKeep . ' lines.';
    }

    /**
     * Delete a file.
     *
     * @param string $filePath
     *
     * @return string
     */
    protected function deleteFile(string $filePath): string
    {
        if ( ! file_exists($filePath) || ! is_file($filePath)) {
            return 'File ' . basename($filePath) . ' could not be found';
        }

        if ($this->option('dry-run') == true) {
            return basename($filePath) . ' would be deleted';
        }

        unlink($filePath);

        return 'Deleted ' . basename($filePath) . '.';
    }

    /**
     * Return all files in a directory, recursively.
     *
     * @param $sourceDirectory
     * @param int $directoryDepth
     * @param false $includeHidden
     *
     * @return array
     */
    protected function directoryMap($sourceDirectory, $directoryDepth = 0, $includeHidden = false)
    {
        if (! file_exists($sourceDirectory)) {
            return [];
        }

        if (($fp = opendir($sourceDirectory))) {
            $filedata = [];
            $new_depth = $directoryDepth - 1;
            $sourceDirectory = rtrim($sourceDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

            while (false !== ($file = readdir($fp))) {
                if ($file === '.' or $file === '..' or ($includeHidden === false && $file[0] === '.')) {
                    continue;
                }

                is_dir($sourceDirectory.$file) && $file .= DIRECTORY_SEPARATOR;

                if (($directoryDepth < 1 or $new_depth > 0) && is_dir($sourceDirectory.$file)) {
                    $filedata[$file] = $this->directoryMap($sourceDirectory.$file, $new_depth, $includeHidden);
                } else {
                    $filedata[] = $file;
                }
            }

            closedir($fp);

            return $filedata;
        }
    }

}
