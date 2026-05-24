<?php
namespace tachyon\components;

class FilesManager
{
    /** @const Folder for uploading files */
    const UPLOAD_DIR = '../runtime/uploads/';
    /** @const Folder for downloading parts of files */
    const CHUNKS_DIR = self::UPLOAD_DIR . 'chunks/';

    /**
     * Sanitizes the filename to protect against Path Traversal
     *
     * @param string $filename
     * @return string
     * @throws \InvalidArgumentException
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new \InvalidArgumentException('Invalid filename.');
        }
        return $filename;
    }

    /**
     * Checks if the path is inside the UPLOAD_DIR directory
     */
    private function isPathInUploads(string $path): bool
    {
        $uploadsDir = realpath(self::UPLOAD_DIR);
        if ($uploadsDir === false) {
            return false;
        }

        $targetPath = file_exists($path) ? $path : dirname($path);
        $realTargetPath = realpath($targetPath);

        if ($realTargetPath === false) {
            return false;
        }

        $uploadsDir = str_replace('\\', '/', $uploadsDir);
        $realTargetPath = str_replace('\\', '/', $realTargetPath);

        return str_starts_with($realTargetPath, $uploadsDir);
    }

    /**
     * Gluing a file
     *
     * @param array $files chunk file names
     * @param string $fileName name of the file to be written to
     */
    public function spliceChunks(array $files, string $fileName): bool
    {
        try {
            $fileName = $this->sanitizeFilename($fileName);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        $spliceFileName = self::UPLOAD_DIR . $fileName;
        
        try {
            $success = $this->writeFile($spliceFileName, '');
        } catch (\Exception $e) {
            return false;
        }

        foreach ($files as $file) {
            try {
                $file = $this->sanitizeFilename($file);
            } catch (\InvalidArgumentException $e) {
                continue;
            }
            $chunkFileName = self::CHUNKS_DIR . $file;
            if ($fileContents = $this->readFile($chunkFileName)) {
                try {
                    $success = $success && $this->writeFile($spliceFileName, $fileContents, true);
                } catch (\Exception $e) {
                    $success = false;
                }
            }
            $this->deleteFile($chunkFileName);
        }
        return $success;
    }

    /**
     * Uploading part of a file to the server
     */
    public function saveChunk(string $tmpName): ?bool
    {
        if ($fileContents = $this->readFile($tmpName)) {
            $fileNum = isset($_GET['fileNum']) ? (int)$_GET['fileNum'] : 0;
            try {
                return $this->writeFile(self::CHUNKS_DIR . 'chunk_' . str_pad($fileNum, 4, 0, STR_PAD_LEFT), $this->base64ToData($fileContents));
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Number of pieces saved. We count from 0
     */
    public function getChunkNames(): array
    {
        return $this->getDirFileNames(self::CHUNKS_DIR);
    }

    public function deleteFile(string $fileName): bool
    {
        if (!is_file($fileName)) {
            return false;
        }
        if (!$this->isPathInUploads($fileName)) {
            return false;
        }
        return unlink($fileName);
    }

    public function readFile(string $fileName): false | string
    {
        if (!is_file($fileName)) {
            return false;
        }
        if (!$this->isPathInUploads($fileName) && !is_uploaded_file($fileName)) {
            return false;
        }
        // if content is written to the file, wait
        while (
               !$handle = fopen($fileName, 'r')
            or !flock($handle, LOCK_EX|LOCK_NB, $wouldBlock)
        ) {
            sleep(1);
        }
        $fileContents = fread($handle, filesize($fileName));
        fclose($handle);
        return $fileContents;
    }

    public function writeFile(string $fileName, string $fileContents, bool $append = false): true
    {
        if (!$this->isPathInUploads($fileName)) {
            throw new \InvalidArgumentException('Writing outside uploads directory is not allowed.');
        }
        while (
               !$handle = fopen($fileName, $append ? 'a' : 'w')
            or !flock($handle, LOCK_EX, $wouldBlock)
        ) {
            sleep(1);
        }
        fwrite($handle, $fileContents);
        fclose($handle);
        return true;
    }

    /**
     * Array of files in a folder
     */
    public function getDirFileNames(string $dirPath): array
    {
        if (!$this->isPathInUploads($dirPath)) {
            return [];
        }
        $fileNames = [];
        $dir = dir($dirPath);
        while ($fileName = $dir->read()) {
            if ($fileName[0] != '.') {
                $fileNames[] = $fileName;
            };
        }
        $dir->close();
        return $fileNames;
    }

    public function base64ToData(string $string): false | string
    {
        return base64_decode(substr($string, strpos($string, 'base64') + 7));
    }
}
