<?php

namespace App\Services\Settings;

use App\Support\SqliteDatabaseFile;
use FilesystemIterator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

final class MomFullBackupArchive
{
    public const string FORMAT = 'medca-full-backup';

    /** Oldest readable manifest version (DB + storage trees only). */
    public const int VERSION_MIN = 1;

    /** Current written manifest version (adds full application tree under project/). */
    public const int VERSION_WRITE = 2;

    public const string ARCHIVE_DB_ENTRY = 'db.sqlite';

    public const string ARCHIVE_PUBLIC_PREFIX = 'files/public';

    public const string ARCHIVE_PRIVATE_PREFIX = 'files/private';

    public const string ARCHIVE_PROJECT_PREFIX = 'project';

    public const string MANIFEST_ENTRY = 'manifest.json';

    /**
     * Relative paths under the application root excluded from project/ (noise or recursion).
     *
     * @var list<string>
     */
    private const EXCLUDED_PROJECT_PREFIXES = [
        '.git',
        'node_modules',
        'storage/app/backups',
    ];

    public function __construct(
        private readonly ?string $sqlitePath,
        private readonly string $publicRoot,
        private readonly string $privateRoot,
        private readonly string $applicationRoot,
    ) {}

    public static function fromApplicationDefaults(): self
    {
        return new self(
            SqliteDatabaseFile::defaultConnectionFilesystemPath(),
            storage_path('app/public'),
            storage_path('app/private'),
            base_path(),
        );
    }

    /**
     * Build a site archive: SQLite DB, storage trees, and (v2) the whole Laravel app tree under project/.
     *
     * @throws RuntimeException
     */
    public function createZipAt(string $zipAbsolutePath): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException(__('PHP Zip extension is not enabled.'));
        }

        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException(__('Full backup requires the default database connection to be SQLite.'));
        }

        if ($this->sqlitePath === null || ! File::isFile($this->sqlitePath)) {
            throw new RuntimeException(__('SQLite database file is missing or not file-backed.'));
        }

        if (! SqliteDatabaseFile::startsWithSqliteMagic($this->sqlitePath)) {
            throw new RuntimeException(__('The active database file is not a valid SQLite database.'));
        }

        File::ensureDirectoryExists(dirname($zipAbsolutePath));

        $zip = new ZipArchive;
        $opened = $zip->open($zipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException(__('Could not create the backup archive file.'));
        }

        try {
            $manifest = [
                'format' => self::FORMAT,
                'version' => self::VERSION_WRITE,
                'scope' => 'full_site',
                'generated_at' => now()->toIso8601String(),
                'database_driver' => 'sqlite',
                'database_file' => self::ARCHIVE_DB_ENTRY,
                'paths' => [
                    'public_prefix' => self::ARCHIVE_PUBLIC_PREFIX,
                    'private_prefix' => self::ARCHIVE_PRIVATE_PREFIX,
                    'project_prefix' => self::ARCHIVE_PROJECT_PREFIX,
                ],
            ];

            $encoded = json_encode($manifest, JSON_PRETTY_PRINT);
            if ($encoded === false) {
                throw new RuntimeException(__('Could not build backup manifest.'));
            }

            $zip->addFromString(self::MANIFEST_ENTRY, $encoded);

            if (! $zip->addFile($this->sqlitePath, self::ARCHIVE_DB_ENTRY)) {
                throw new RuntimeException(__('Could not add the database file to the archive.'));
            }

            $this->addTreeToZip($zip, $this->publicRoot, self::ARCHIVE_PUBLIC_PREFIX);
            $this->addTreeToZip($zip, $this->privateRoot, self::ARCHIVE_PRIVATE_PREFIX);
            $this->addApplicationTreeToZip($zip, $this->applicationRoot, self::ARCHIVE_PROJECT_PREFIX);
        } finally {
            $zip->close();
        }
    }

    /**
     * Restore site state from an archive created by this service.
     *
     * @throws RuntimeException
     */
    public function restoreFromZipFile(string $zipAbsolutePath, ?string $safetySnapshotZipPath = null): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException(__('PHP Zip extension is not enabled.'));
        }

        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException(__('Full restore requires the default database connection to be SQLite.'));
        }

        if ($this->sqlitePath === null || ! File::isFile($this->sqlitePath)) {
            throw new RuntimeException(__('SQLite database file is missing or not file-backed.'));
        }

        $extractRoot = storage_path('app/backups/extract-'.uniqid('mom_', true));
        File::ensureDirectoryExists($extractRoot);

        try {
            $this->extractZipEntriesSafely($zipAbsolutePath, $extractRoot);

            $manifestPath = $extractRoot.DIRECTORY_SEPARATOR.self::MANIFEST_ENTRY;
            if (! File::isFile($manifestPath)) {
                throw new RuntimeException(__('Backup archive is missing manifest.json.'));
            }

            $manifest = $this->decodeManifest(File::get($manifestPath));

            $dbPathInExtract = $extractRoot.DIRECTORY_SEPARATOR.self::ARCHIVE_DB_ENTRY;
            if (! File::isFile($dbPathInExtract)) {
                throw new RuntimeException(__('Backup archive is missing the database file.'));
            }

            if (! SqliteDatabaseFile::startsWithSqliteMagic($dbPathInExtract)) {
                throw new RuntimeException(__('The database inside the archive is not a valid SQLite file.'));
            }

            if ($safetySnapshotZipPath !== null) {
                self::fromApplicationDefaults()->createZipAt($safetySnapshotZipPath);
            }

            $publicSrc = $extractRoot.DIRECTORY_SEPARATOR.self::ARCHIVE_PUBLIC_PREFIX;
            if (File::isDirectory($this->publicRoot)) {
                File::deleteDirectory($this->publicRoot);
            }
            File::ensureDirectoryExists($this->publicRoot);
            if (File::isDirectory($publicSrc)) {
                File::copyDirectory($publicSrc, $this->publicRoot);
            }

            $privateSrc = $extractRoot.DIRECTORY_SEPARATOR.self::ARCHIVE_PRIVATE_PREFIX;
            if (File::isDirectory($this->privateRoot)) {
                File::deleteDirectory($this->privateRoot);
            }
            File::ensureDirectoryExists($this->privateRoot);
            if (File::isDirectory($privateSrc)) {
                File::copyDirectory($privateSrc, $this->privateRoot);
            }

            $manifestVersion = (int) ($manifest['version'] ?? 1);
            if ($manifestVersion >= self::VERSION_WRITE) {
                $projectSrc = $extractRoot.DIRECTORY_SEPARATOR.self::ARCHIVE_PROJECT_PREFIX;
                if (File::isDirectory($projectSrc)) {
                    $this->restoreApplicationTreeFromExtract($projectSrc, $this->applicationRoot);
                }
            }

            File::copy($dbPathInExtract, $this->sqlitePath);

            if (config('database.default') === 'sqlite') {
                DB::purge('sqlite');
            }
        } finally {
            if (File::isDirectory($extractRoot)) {
                File::deleteDirectory($extractRoot);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeManifest(string $json): array
    {
        $data = json_decode($json, true);
        if (! is_array($data)) {
            throw new RuntimeException(__('Backup manifest.json is not valid JSON.'));
        }

        if (($data['format'] ?? null) !== self::FORMAT) {
            throw new RuntimeException(__('Invalid backup archive (unrecognized format).'));
        }

        $version = (int) ($data['version'] ?? 0);
        if ($version < self::VERSION_MIN || $version > self::VERSION_WRITE) {
            throw new RuntimeException(__('Invalid backup archive (unsupported version).'));
        }

        return $data;
    }

    private function addTreeToZip(ZipArchive $zip, string $absoluteDir, string $zipPrefix): void
    {
        File::ensureDirectoryExists($absoluteDir);

        $absoluteDir = realpath($absoluteDir);
        if ($absoluteDir === false) {
            throw new RuntimeException(__('Could not resolve a storage path for the archive.'));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            $path = $file->getPathname();
            $relative = substr($path, strlen($absoluteDir) + 1);
            $relative = str_replace('\\', '/', $relative);

            if ($file->isDir()) {
                continue;
            }

            $zipPath = $zipPrefix.'/'.$relative;
            if (! $zip->addFile($path, $zipPath)) {
                throw new RuntimeException(__('Could not add :path to the archive.', ['path' => $relative]));
            }
        }
    }

    private function addApplicationTreeToZip(ZipArchive $zip, string $absoluteDir, string $zipPrefix): void
    {
        File::ensureDirectoryExists($absoluteDir);

        $absoluteDirReal = realpath($absoluteDir);
        if ($absoluteDirReal === false) {
            throw new RuntimeException(__('Could not resolve application root for the archive.'));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteDirReal, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            if ($file->isLink()) {
                continue;
            }

            $path = $file->getPathname();
            $relative = substr($path, strlen($absoluteDirReal) + 1);
            $relative = str_replace('\\', '/', $relative);

            if ($file->isDir()) {
                continue;
            }

            if ($this->isExcludedProjectRelativePath($relative)) {
                continue;
            }

            $zipPath = $zipPrefix.'/'.$relative;
            if (! $zip->addFile($path, $zipPath)) {
                throw new RuntimeException(__('Could not add :path to the archive.', ['path' => $relative]));
            }
        }
    }

    private function isExcludedProjectRelativePath(string $relative): bool
    {
        $relative = str_replace('\\', '/', $relative);

        foreach (self::EXCLUDED_PROJECT_PREFIXES as $prefix) {
            if ($relative === $prefix || str_starts_with($relative, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    private function restoreApplicationTreeFromExtract(string $extractedProjectRoot, string $applicationRoot): void
    {
        $extractedReal = realpath($extractedProjectRoot);
        $applicationReal = realpath($applicationRoot);

        if ($extractedReal === false || $applicationReal === false) {
            throw new RuntimeException(__('Could not resolve paths for application restore.'));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractedReal, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if ($file->isLink()) {
                continue;
            }

            $srcPath = $file->getPathname();
            $relative = substr($srcPath, strlen($extractedReal) + 1);
            $relative = str_replace('\\', '/', $relative);

            if (str_contains($relative, '..')) {
                throw new RuntimeException(__('Unsafe relative path during application restore.'));
            }

            $destPath = $applicationReal.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            File::ensureDirectoryExists(dirname($destPath));

            $destParentReal = realpath(dirname($destPath));
            if (
                $destParentReal === false
                || ($destParentReal !== $applicationReal && ! str_starts_with($destParentReal, $applicationReal.DIRECTORY_SEPARATOR))
            ) {
                throw new RuntimeException(__('Blocked application restore path (outside project root).'));
            }

            if (! File::copy($srcPath, $destPath)) {
                throw new RuntimeException(__('Could not write restored application file :path.', ['path' => $relative]));
            }
        }
    }

    private function extractZipEntriesSafely(string $zipAbsolutePath, string $extractRoot): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipAbsolutePath) !== true) {
            throw new RuntimeException(__('Could not open the uploaded backup archive.'));
        }

        try {
            $extractRootReal = realpath($extractRoot);
            if ($extractRootReal === false) {
                throw new RuntimeException(__('Temporary extract path is invalid.'));
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    throw new RuntimeException(__('Could not read the backup archive.'));
                }

                $name = (string) $stat['name'];
                if (str_ends_with($name, '/')) {
                    continue;
                }

                $normalized = str_replace('\\', '/', $name);
                $normalized = ltrim($normalized, '/');
                if (str_starts_with($normalized, './')) {
                    $normalized = substr($normalized, 2);
                }

                if ($normalized === '' || str_contains($normalized, '..')) {
                    throw new RuntimeException(__('Unsafe path inside backup archive.'));
                }

                $target = $extractRootReal.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);
                $targetDirectory = dirname($target);

                if (! File::isDirectory($targetDirectory)) {
                    File::ensureDirectoryExists($targetDirectory);
                }

                $targetDirectoryReal = realpath($targetDirectory);
                if (
                    $targetDirectoryReal === false
                    || ($targetDirectoryReal !== $extractRootReal && ! str_starts_with($targetDirectoryReal, $extractRootReal.DIRECTORY_SEPARATOR))
                ) {
                    throw new RuntimeException(__('Blocked zip path traversal in backup archive.'));
                }

                $streamIn = $zip->getStream($normalized);
                if ($streamIn === false) {
                    throw new RuntimeException(__('Could not read an entry from the backup archive.'));
                }

                $streamOut = fopen($target, 'wb');
                if ($streamOut === false) {
                    fclose($streamIn);

                    throw new RuntimeException(__('Could not write extracted backup files.'));
                }

                stream_copy_to_stream($streamIn, $streamOut);
                fclose($streamIn);
                fclose($streamOut);
            }
        } finally {
            $zip->close();
        }
    }
}
