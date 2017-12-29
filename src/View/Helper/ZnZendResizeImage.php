<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Make resized copy of image and return path for use in HTML <img>
 */
class ZnZendResizeImage extends AbstractHelper
{
    /**
     * __invoke
     *
     * Creates resized copy of image if it does not exist and returns path.
     *
     * Example: __invoke('/public/images/test.jpg', 150, 100)
     * Result for centered image: '/public/images/150x100/test_150x100c.jpg'
     * Result for non-centered image: '/public/images/150x100/test_150x100.jpg'
     *
     * Original image need not exist if resized copy is available and $overwrite is false.
     *
     * @param  string $imagePath Path to image file, relative to $webRoot, as used in <img src="">
     * @param  int    $width     Maximum width for resized image
     * @param  int    $height    Maximum height for resized image
     * @param  bool   $center    Default = false. Optional flag to center resized image
     *                           in box defined by $width x $height
     * @param  int    $quality   Default = 100. Optional quality for resized image from 0 to 100
     * @param  bool   $overwrite Default = false. Optional flag to overwrite existing resized image
     * @param  string $webRoot   Defaults to $_SERVER['DOCUMENT_ROOT']. Absolute server path for web root
     * @return string Path to resized copy of image for use in HTML <img>, relative to $webRoot.
     *                An empty string is returned upon any failure such as write permissions, as returning
     *                the original path will likely break the layout expecting a different size
     */
    public function __invoke(
        $imagePath,
        $width,
        $height,
        $center = false,
        $quality = 100,
        $overwrite = false,
        $webRoot = null
    ) {
        $failure = '';

        if (null === $webRoot) {
            $webRoot = $_SERVER['DOCUMENT_ROOT'];
        }

        if (! extension_loaded('gd')) {
            return $failure;
        }

        // Compute subfolder and new filename
        $pathParts = pathinfo($imagePath);
        $resizedFolder = sprintf(
            '%s/%dx%d',
            $pathParts['dirname'],
            $width,
            $height
        );
        $resizedPath = sprintf(
            '%s/%s_%dx%d%s.%s',
            $resizedFolder,
            $pathParts['filename'],
            $width,
            $height,
            ($center ? 'c' : ''),
            $pathParts['extension']
        );
        if (! $overwrite && file_exists($webRoot . $resizedPath)) {
            return $resizedPath;
        }

        // Check if original image exists
        if (! file_exists($webRoot . $imagePath)) {
            return $failure;
        }

        // Detect type of image
        $imageInfo = getimagesize($webRoot . $imagePath);
        if (false === $imageInfo) {
            return $failure;
        }
        switch ($imageInfo[2]) {
            case IMAGETYPE_GIF:
                $type = 'gif';
                break;
            case IMAGETYPE_PNG:
                $type = 'png';
                break;
            case IMAGETYPE_JPEG:
                $type = 'jpeg';
                break;
            default:
                return $failure;
        }

        // Do not create resized copy if current image dimensions are the same or smaller
        $currWidth  = $imageInfo[0];
        $currHeight = $imageInfo[1];
        if ($currWidth <= $width && $currHeight <= $height) {
            return $imagePath;
        }

        // Create canvas for resized copy of image to fit box $width x $height
        if ($currWidth >= $currHeight) {
            $newWidth  = $width;
            $newHeight = ($currHeight / $currWidth) * $newWidth;
            if ($newHeight > $height) {
                $newHeight = $height;
                $newWidth = ($currWidth / $currHeight) * $newHeight;
            }
        } else {
            $newHeight = $height;
            $newWidth  = ($currWidth / $currHeight) * $newHeight;
            if ($newWidth > $width) {
                $newWidth = $width;
                $newHeight = ($currHeight / $currWidth) * $newWidth;
            }
        }
        $image = imagecreatefromstring(file_get_contents($webRoot . $imagePath));
        $resizedImage = empty($center)
                      ? imagecreatetruecolor($newWidth, $newHeight)
                      : imagecreatetruecolor($width, $height);

        // Retain transparency for PNG and GIF
        if ('png' == $type || 'gif' == $type) {
            imagecolortransparent($resizedImage, imagecolorallocate($resizedImage, 0, 0, 0));
        }

        // Position of resized image in canvas
        $destX = empty($center) ? 0 : (int) (($width - $newWidth) / 2);
        $destY = empty($center) ? 0 : (int) (($height - $newHeight) / 2);
        imagecopyresampled(
            $resizedImage,
            $image,
            $destX,
            $destY,
            0,
            0,
            $newWidth,
            $newHeight,
            $currWidth,
            $currHeight
        );

        // Create subfolder
        if (! file_exists($webRoot . $resizedFolder)) {
            if (! mkdir($webRoot . $resizedFolder, 0755)) {
                return $failure;
            }
        }

        // Create file
        $imageFunc = 'image' . $type;
        if ('jpeg' == $type) {
            $success = $imageFunc($resizedImage, $webRoot . $resizedPath, $quality);
        } else {
            $success = $imageFunc($resizedImage, $webRoot . $resizedPath);
        }
        imagedestroy($resizedImage);

        return ($success ? $resizedPath : $failure);
    }
}
