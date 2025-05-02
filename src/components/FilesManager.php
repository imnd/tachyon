<?php
namespace tachyon\components;

class FilesManager
{
    /** @const Папка для загрузки файлов */
    const UPLOAD_DIR = '../runtime/uploads/';
    /** @const Папка для загрузки частей файлов */
    const CHUNKS_DIR = self::UPLOAD_DIR . 'chunks/';

    /**
     * Склеивание файла
     *
     * @param array $files имена файлов кусков
     * @param string $fileName имя файла, в который будет запись
     */
    public function spliceChunks(array $files, string $fileName): bool
    {
        $spliceFileName = self::UPLOAD_DIR . $fileName;
        $success = $this->writeFile($spliceFileName, '');
        foreach ($files as $file) {
            $chunkFileName = self::CHUNKS_DIR . $file;
            if ($fileContents = $this->readFile($chunkFileName, true)) {
                $success = $success && $this->writeFile($spliceFileName, $fileContents, true);
            }
            self::deleteFile($chunkFileName);
        }
        return $success;
    }

    /**
     * Загрузка части файла на сервер
     */
    public function saveChunk(string $tmpName): ?bool
    {
        if ($fileContents = $this->readFile($tmpName)) {
            return $this->writeFile(self::CHUNKS_DIR . 'chunk_' . str_pad($_GET['fileNum'], 4, 0, STR_PAD_LEFT), $this->base64ToData($fileContents));
        }
        return false;
    }

    /**
     * Количество сохраненных кусков. Считаем с нуля
     */
    public function getChunkNames(): array
    {
        return $this->getDirFileNames(self::CHUNKS_DIR);
    }

    /**
     * Удаление файла.
     */
    public function deleteFile(string $fileName): bool
    {
        if (!is_file($fileName)) {
            return false;
        }
        return unlink($fileName);
    }

    /**
     * Чтение файла.
     */
    public function readFile(string $fileName): false | string
    {
        if (!is_file($fileName)) {
            return false;
        }
        // если в файл пишется контент ждем
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

    /**
     * Запись в файл.
     */
    public function writeFile(string $fileName, string $fileContents, bool $append = false): true
    {
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
     * Массив файлов в папке
     */
    public function getDirFileNames(string $dirPath): array
    {
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
