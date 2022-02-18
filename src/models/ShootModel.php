<?php
/**
 * Shoot plugin for Craft CMS 3.x
 *
 * Screenshot stuff.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2019 Værsågod
 */

namespace vaersaagod\shoot\models;

use craft\base\Model;
use craft\helpers\FileHelper;
use craft\helpers\Image;

/**
 * @author    Værsågod
 * @package   Shoot
 * @since     2.0.0
 */
class ShootModel extends Model
{

    /** @var string|null */
    public $path;

    /** @var string|null */
    public $extension;

    /** @var string|null */
    public $mimeType;

    /** @var int|null */
    public $size;

    /** @var int|null */
    public $width;

    /** @var int|null */
    public $height;

    /** @var string|null */
    public $url;

    /** @var string|null */
    public $filename;


    /**
     * ShootModel constructor.
     * @param null $imagePath
     * @param null $imageUrl
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($imagePath = null, $imageUrl = null)
    {

        parent::__construct();

        if ($imagePath != 'null') {

            $this->path = $imagePath;
            $this->extension = FileHelper::getExtensionByMimeType(FileHelper::getMimeType($imagePath));
            $this->mimeType = FileHelper::getMimeType($imagePath);
            $this->size = Image::imageSize($imagePath);

            $imageInfo = @getimagesize($imagePath);

            if ($imageInfo && \is_array($imageInfo)) {
                $this->width = $imageInfo[0] ?? null;
                $this->height = $imageInfo[1] ?? null;
            }
        }

        if ($imageUrl != 'null') {
            $this->url = $imageUrl;
        }

        $this->filename = \basename($imagePath ?? $imageUrl ?? '');

    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->url;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @return mixed
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return string|null
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $unit
     * @param int $precision
     * @return float|mixed
     */
    public function getSize($unit = 'b', $precision = 2)
    {
        $unit = \strtolower($unit);

        switch ($unit) {
            case "g":
            case "gb":
                return \round(((int)$this->size) / 1024 / 1024 / 1024, $precision);
                break;
            case "m":
            case "mb":
                return \round(((int)$this->size) / 1024 / 1024, $precision);
                break;
            case "k":
            case "kb":
                return \round(((int)$this->size) / 1024, $precision);
                break;
            default:
                return $this->size;
        }
    }

    /**
     * @return string
     */
    public function getDataUri()
    {
        $imageData = $this->getBase64Encoded();
        return sprintf('data:image/%s;base64,%s', $this->extension, $imageData);
    }

    /**
     * @return string
     */
    public function getBase64Encoded()
    {
        $image = \file_get_contents($this->path);
        return \base64_encode($image);
    }

}
