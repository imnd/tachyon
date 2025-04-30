<?php

namespace tachyon\components;

use RuntimeException;

/**
 * Класс работы с файлами
 *
 * @author imndsu@gmail.com
 */
class Upload
{
    private $allowedTypes;
    private $uploadPath;
    private $thumbDir;
    /* Превьюшки */
    private $thumbWidth;
    private $thumbHeight;
    /** Jpeg качество */
    private int $jpegQuality = 80;
    private array $imageTypes = [
        'jpg' => IMAGETYPE_JPEG,
        'jpeg' => IMAGETYPE_JPEG,
        'png' => IMAGETYPE_PNG,
        'gif' => IMAGETYPE_GIF,
    ];

    /**
     * @var Encrypt $encrypt
     */
    protected Encrypt $encrypt;

    public function __construct(Encrypt $encrypt, $config)
    {
        $this->encrypt = $encrypt;
        $this->allowedTypes = $config['allowedTypes'] ?? '*';
        $this->uploadPath = $config['uploadPath'] ?? '/';
        $this->thumbDir = $config['thumbDir'] ?? '';
        $this->thumbWidth = $config['thumbWidth'];
        $this->thumbHeight = $config['thumbHeight'];
    }

    /**
     * Сохраняет файлы на сервере
     *
     * @param $files
     * @param $fileInputName
     *
     * @return array
     */
    public function uploadFiles($files, $fileInputName): array
    {
        $images = [];
        if (!$this->_checkUploadPath()) {
            return $images;
        }
        $files = $this->_prepareFilesArray($files, $fileInputName);
        foreach ($files as $file) {
            if (!empty($file['name'])) {
                $fileName = $this->_uploadFile($file);
                if ($fileName !== false) {
                    $this->thumb($fileName);
                    $images[] = $fileName;
                }
            }
        }
        return $images;
    }

    /**
     * генерация тумбочки при сохранении картинки
     *
     * @param string $thumbName
     */
    public function thumb(string $thumbName): void
    {
        $thumbPath = $this->uploadPath . $this->thumbDir . '/';
        if (!file_exists($thumbPath)) {
            if (!mkdir($thumbPath) && !is_dir($thumbPath)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $thumbPath));
            }
        }
        $picName = $this->uploadPath . $thumbName;
        $thumbFullName = $thumbPath . $thumbName;
        if (!file_exists($thumbFullName) || filemtime($thumbFullName) < filemtime($picName)) {
            if (@copy($picName, $thumbFullName)) {
                $thumb = imagecreatefromjpeg($thumbFullName);
                $thumbWidth = imagesx($thumb);
                $thumbHeight = imagesy($thumb);
                if ($thumbWidth > $thumbHeight) {
                    $ratio = $this->thumbHeight / $thumbHeight;
                    $width = $thumbWidth * $ratio;
                    $height = $this->thumbHeight;
                } else {
                    $ratio = $this->thumbWidth / $thumbWidth;
                    $height = $this->thumbHeight * $ratio;
                    $width = $this->thumbWidth;
                }
                $this->_resize($thumb, $width, $height, $thumbWidth, $thumbHeight);
                $fileExt = $this->_getExt($thumbName);
                $thumbType = $this->imageTypes[$fileExt];
                $this->_save($thumb, $thumbFullName, $thumbType);
            }
        }
    }

    /**
     * получает расширение файла
     *
     * @param string $fileName
     *
     * @return string
     */
    private function _getExt(string $fileName): string
    {
        $arr = explode('.', $fileName);
        return end($arr);
    }

    /**
     * проверяет тип файла
     *
     * @param string $fileName
     *
     * @return boolean
     */
    private function _isAllowedFiletype(string $fileName): bool
    {
        if ($this->allowedTypes === '*') {
            return false;
        }
        $fileExt = $this->_getExt($fileName);
        $allowedTypes = explode('|', $this->allowedTypes);
        if (!in_array($fileExt, $allowedTypes)) {
            return false;
        }
        return false;
    }

    /**
     * проверяет путь файла
     *
     * @return boolean
     */
    private function _checkUploadPath(): bool
    {
        if (realpath($this->uploadPath) !== false) {
            $this->uploadPath = str_replace("\\", "/", realpath($this->uploadPath));
        }
        if (!is_dir($this->uploadPath)) {
            return false;
        }
        $this->uploadPath .= '/';
        return true;
    }

    /**
     * заливает файл на сервак
     *
     * @param $file string
     *
     * @return boolean|string
     */
    private function _uploadFile($file)
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        $fileTemp = $file['tmp_name'];
        if (filesize($fileTemp) == 0) {
            return false;
        }
        $fileName = $file['name'];
        if (!$this->_isAllowedFiletype($fileName)) {
            return false;
        }
        $fileExt = $this->_getExt($fileName);
        $fileName = $this->encrypt->randString() . ".$fileExt";
        $filePath = $this->uploadPath . $fileName;
        // перемещаем файл
        if (!@copy($fileTemp, $filePath)) {
            if (!@move_uploaded_file($fileTemp, $filePath)) {
                return false;
            }
        }
        return $fileName;
    }

    /**
     * изменяет размер файла
     *
     * @param $image resource
     * @param $width integer
     * @param $height integer
     * @param $oldWidth integer
     * @param $oldHeight integer
     *
     * @return resource
     */
    private function _resize($image, $width, $height, $oldWidth, $oldHeight)
    {
        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $oldWidth, $oldHeight);
        return $newImage;
    }

    /**
     * Output image to browser or file
     *
     * @param $image resource
     * @param $fileName string
     * @param $imageType
     */
    private function _save($image, $fileName, $imageType = IMAGETYPE_JPEG): void
    {
        if ($imageType == IMAGETYPE_JPEG) {
            imagejpeg($image, $fileName, $this->jpegQuality);
        } elseif ($imageType == IMAGETYPE_GIF) {
            imagegif($image, $fileName);
        } elseif ($imageType == IMAGETYPE_PNG) {
            imagepng($image, $fileName);
        }
    }

    /**
     * превращаем массив $files в удобоваримый вид
     *
     * @param $files array
     * @param $fileInputName string
     *
     * @return array
     */
    private function _prepareFilesArray($files, $fileInputName): array
    {
        $filesCnt = count($files[$fileInputName]['name']);
        for ($i = 0; $i < $filesCnt; $i++) {
            $files[$fileInputName . $i] = [
                'name' => $files[$fileInputName]['name'][$i],
                'type' => $files[$fileInputName]['type'][$i],
                'tmp_name' => $files[$fileInputName]['tmp_name'][$i],
                'error' => $files[$fileInputName]['error'][$i],
                'size' => $files[$fileInputName]['size'][$i],
            ];
        }
        unset($files[$fileInputName]);
        return $files;
    }
}
