<?php

namespace Crm\IssuesModule\Models\Pdf;

use Imagick;

class Converter
{
    private $tempFolder;

    private $mimeType = 'image/jpeg';

    private $type = 'jpeg';

    private $quality = 90;

    public function __construct()
    {
        $this->tempFolder = sys_get_temp_dir();
        if (!file_exists($this->tempFolder)) {
            throw new ConverterError("Cannot initialize temp folder '{$this->tempFolder}'");
        }
    }

    public function generateImages($pdfPath, $width, $height)
    {

        // small - 800x1200
        // large - 1600x2400

        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);
        $imagick->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 512);
        if (!$imagick) {
            throw new ConverterError("Cannot open pdf file '$pdfPath'.", 500);
        }

        $pages = $imagick->getNumberImages();

        $result = [];

        for ($i = 0; $i < $pages; $i++) {
            $filePath = $this->getFilePath();
            list($width, $height) = $this->generateImage($imagick, $width, $height, $i, $filePath);
            $result[$i] = [
                'file' => $filePath,
                'width' => $width,
                'height' => $height,
            ];
        }

        return $result;
    }

    public function generateCover($pdfPath)
    {
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);
        $imagick->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 512);
        if (!$imagick) {
            throw new ConverterError("Cannot open pdf file '$pdfPath'.", 500);
        }

        $filePath = $this->getFilePath();
        list($width, $height) = $this->generateImage($imagick, 300, 0, 0, $filePath);
        return [
            'file' => $filePath,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function getFilePath()
    {
        return tempnam($this->tempFolder, 'issues');
    }

    private function generateImage(Imagick $imagick, $width, $height, $page, $outputFile)
    {
        $imagick->setIteratorIndex($page);
        $imagick->scaleImage($width, $height);
        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality($this->quality);
        $imagick->setimageformat($this->type);
        $imagick->setColorspace(Imagick::COLORSPACE_RGB);
        if ($imagick->getImageAlphaChannel()) {
            $imagick->setImageAlphaChannel(11);
            $imagick->setImageBackgroundColor('white');
            $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        }
        $imagick->writeImage($outputFile);
        return [$imagick->getImageWidth(), $imagick->getImageHeight()];
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }
}
