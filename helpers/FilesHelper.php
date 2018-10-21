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
        self::deleteFile($spliceFileName);
        if (!$handle = fopen($spliceFileName, 'a')) {
            return false;
        }
        flock($handle, LOCK_EX);
        $success = true;
        foreach ($files as $file) {
            $chunkFileName = self::CHUNKS_DIR . $file;
            if ($fileContents = self::readFile($chunkFileName, true)) {
                $success = $success && fwrite($handle, self::base64ToData($fileContents));
            }
            self::deleteFile($chunkFileName);
        }
        fclose($handle);
        return $success;
        
        /*$success = self::writeFile($spliceFileName, '');
        foreach ($files as $file) {
            $chunkFileName = self::CHUNKS_DIR . $file;
            if ($fileContents = self::readFile($chunkFileName, true)) {
                $success = $success && self::writeFile($spliceFileName, self::base64ToData($fileContents), true);
            }
            self::deleteFile($chunkFileName);
        }
        return $success;*/
    }

    /**
     * Загрузка части файла на сервер
     * 
     * @param string $name
     * @return boolean
     */
    public static function saveChunk($name)
    {
        if ($fileContents = self::readFile($name)) {
            return self::writeFile(self::CHUNKS_DIR . 'chunk_' . str_pad($_GET['fileNum'], 4, 0, STR_PAD_LEFT), $fileContents);
        }
        return false;
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
        $handle = fopen($fileName, 'r');
        // если в файл пишется контент ждем
        while (!flock($handle, LOCK_EX|LOCK_NB, $wouldblock)) {
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
        if (!$handle = fopen($fileName, $append ? 'a' : 'w')) {
            return false;
        }
        flock($handle, LOCK_EX);
        fwrite($handle, $fileContents);
        fclose($handle);
        return true;
    }

    /**
     * Количество файлов. Считаем с нуля
     * 
     * @param string $dirPath
     * @return integer
     */
    public static function getFiles($dirPath)
    {
        $files = [];
        $dir = dir($dirPath);
        while ($str = $dir->read()){
            if ($str{0} != '.') {
                $files[] = $str;
            };
        } 
        $dir->close();
        return $files;
    }

    public static function base64ToData($string)
    {
        $data = explode(',', $string);
        return base64_decode($data[1]);
    }
}