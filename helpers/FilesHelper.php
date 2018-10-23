<?php 
namespace tachyon\helpers;

class FilesHelper
{
    /** @const Папка для загрузки файлов */
    const UPLOAD_DIR = '../runtime/uploads/';
    /** @const Папка для загрузки частей файлов */
    const CHUNKS_DIR = self::UPLOAD_DIR . 'chunks/';

    /**
     * Склеивание файла
     * 
     * @param array $files имена файлов кусков
     * @param string $name имя файла, в который будет запись
     * @return boolean
     */
    public static function spliceChunks($files, $fileName)
    {
        $spliceFileName = self::UPLOAD_DIR . $fileName;
        $success = self::writeFile($spliceFileName, '');
        foreach ($files as $file) {
            $chunkFileName = self::CHUNKS_DIR . $file;
            if ($fileContents = self::readFile($chunkFileName, true)) {
                $success = $success && self::writeFile($spliceFileName, $fileContents, true);
            }
            self::deleteFile($chunkFileName);
        }
        return $success;
    }

    /**
     * Загрузка части файла на сервер
     * 
     * @param string $tmpName
     * @return boolean
     */
    public static function saveChunk($tmpName)
    {
        if ($fileContents = self::readFile($tmpName)) {
            return self::writeFile(self::CHUNKS_DIR . 'chunk_' . str_pad($_GET['fileNum'], 4, 0, STR_PAD_LEFT), self::base64ToData($fileContents));
        }
        return false;
    }

    /**
     * Количество сохраненных кусков. Считаем с нуля
     * 
     * @return array
     */
    public static function getChunkNames()
    {
        return self::getDirFileNames(self::CHUNKS_DIR);
    }

    /**
     * Удаление файла.
     * 
     * @param string $fileName
     * @return boolean
     */
    public static function deleteFile($fileName)
    {
        if (!is_file($fileName)) {
            return false;
        }
        return unlink($fileName);
    }

    /**
     * Чтение файла.
     * 
     * @param string $fileName
     * @return string
     */
    public static function readFile($fileName)
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
     * 
     * @param string $fileName
     * @param string $fileContents
     * @return void
     */
    public static function writeFile($fileName, $fileContents, $append = false)
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
     * Количество файлов. Считаем с нуля
     * 
     * @param string $dirPath
     * @return array
     */
    public static function getDirFileNames($dirPath)
    {
        $fileNames = [];
        $dir = dir($dirPath);
        while ($str = $dir->read()){
            if ($str{0} != '.') {
                $fileNames[] = $str;
            };
        } 
        $dir->close();
        return $fileNames;
    }

    public static function base64ToData($string)
    {
        return base64_decode(substr($string, strpos($string, 'base64') + 7));
    }
}