<?php

namespace tachyon\components;

use RuntimeException;

/**
 * File handling class
 *
 * @author imndsu@gmail.com
 */
class Upload
{
    private $allowedTypes;
    private $uploadPath;
    private $thumbDir;
    /* thumbs */
    private $thumbWidth;
    private $thumbHeight;
    /** Jpeg quality */
    private int $jpegQuality = 80;
    private array $imageTypes = [
        'jpg' => IMAGETYPE_JPEG,
        'jpeg' => IMAGETYPE_JPEG,
        'png' => IMAGETYPE_PNG,
        'gif' => IMAGETYPE_GIF,
    ];

    public function __construct(protected Encrypt $encrypt, array $config)
    {
        $this->allowedTypes = $config['allowedTypes'] ?? '*';
        $this->uploadPath = $config['uploadPath'] ?? '/';
        $this->thumbDir = $config['thumbDir'] ?? '';
        $this->thumbWidth = $config['thumbWidth'];
        $this->thumbHeight = $config['thumbHeight'];
    }

    /**
     * Saves files on the server
     */
    public function uploadFiles(array $files, string $fileInputName): array
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
     * Generate a thumb when saving an image
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
                $fileExt = $this->_getExt($thumbName);
                $suffix = $fileExt === 'jpg' ? 'jpeg' : $fileExt;
                $method = "imagecreatefrom$suffix";

                if (!function_exists($method)) {
                    throw new RuntimeException("Unsupported image format: $suffix");
                }

                $sourceImage = $method($thumbFullName);
                $thumbWidth = imagesx($sourceImage);
                $thumbHeight = imagesy($sourceImage);

                // Корректный расчет пропорций (Max Box Fit)
                $ratio = min($this->thumbWidth / $thumbWidth, $this->thumbHeight / $thumbHeight);
                $width = (int)($thumbWidth * $ratio);
                $height = (int)($thumbHeight * $ratio);

                // Перезапись переменной результатом ресайза
                $resizedImage = $this->_resize($sourceImage, $width, $height, $thumbWidth, $thumbHeight);

                $thumbType = $this->imageTypes[$fileExt] ?? IMAGETYPE_JPEG;
                $this->_save($resizedImage, $thumbFullName, $thumbType);

                // Явное освобождение памяти (Критично для старых версий PHP / больших пакетов)
                imagedestroy($sourceImage);
                imagedestroy($resizedImage);
            }
        }
    }

    /**
     * gets the file extension
     */
    private function _getExt(string $fileName): string
    {
        $arr = explode('.', $fileName);
        return end($arr);
    }

    /**
     * Checks file type
     */
    private function _isAllowedFiletype(string $fileName): bool
    {
        $fileExt = strtolower($this->_getExt($fileName));
        
        // Blacklist of executable/dangerous files for protection against RCE
        $blacklist = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'htaccess', 'phar', 'exe', 'sh', 'pl', 'cgi', 'asp', 'aspx', 'jsp'];
        if (in_array($fileExt, $blacklist, true)) {
            return false;
        }

        if ($this->allowedTypes === '*') {
            return true;
        }
        $allowedTypes = explode('|', strtolower($this->allowedTypes));
        return in_array($fileExt, $allowedTypes, true);
    }

    /**
     * Checks the file path
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
     * Uploads the file to the server
     */
    private function _uploadFile(array $file): bool | string
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
        // move the file
        if (!@copy($fileTemp, $filePath)) {
            if (!@move_uploaded_file($fileTemp, $filePath)) {
                return false;
            }
        }
        return $fileName;
    }

    /**
     * change file size
     */
    private function _resize($image, int $width, int $height, int $oldWidth, int $oldHeight)
    {
        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $oldWidth, $oldHeight);

        return $newImage;
    }

    /**
     * Output image to browser or file
     */
    private function _save($image, string $fileName, int $imageType = IMAGETYPE_JPEG): void
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
     * transform the $files array into a digestible form
     */
    private function _prepareFilesArray(array $files, string $fileInputName): array
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
