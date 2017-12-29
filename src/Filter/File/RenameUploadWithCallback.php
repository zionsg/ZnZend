<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Filter\File;

use Zend\Filter\File\RenameUpload;
use ZnZend\Filter\Exception;

/**
 * Added option of using callback to rename uploaded file.
 * If no callback is provided, defaults to RenameUpload behaviour.
 */
class RenameUploadWithCallback extends RenameUpload
{
    /**
     * @var array
     */
    protected $options = [
        'target'               => null,
        'callback'             => null,
        'use_upload_name'      => false,
        'use_upload_extension' => false,
        'overwrite'            => false,
        'randomize'            => false,
    ];

    /**
     * Set callback for renaming file
     *
     * $callback must take in 2 arguments ($uploadData, $targetDir) and return string.
     * If $callback is string, class name is assumed and __invoke() will be called.
     *
     * @see    getFinalTarget() for more information on $uploadData and $targetDir
     * @param  callable|string $callback
     * @throws Exception\InvalidArgumentException
     * @return self
     */
    public function setCallback($callback)
    {
        if (is_string($callback)) {
            $callback = new $callback();
        }
        if (! is_callable($callback)) {
            throw new Exception\InvalidArgumentException('Invalid callback');
        }

        $this->options['callback'] = $callback;
        return $this;
    }

    /**
     * Get callback for renaming file
     *
     * If no callback was set, default one is provided.
     *
     * @return callback
     */
    public function getCallback()
    {
        return $this->options['callback'];
    }

    /**
     * Get final target file path
     *
     * Uses callback if provided. Defaults to parent behaviour if callback not provided.
     *
     * @param  array $uploadData $_FILES array
     * @return string
     */
    protected function getFinalTarget($uploadData)
    {
        $callback = $this->getCallback();
        if (! is_callable($callback)) {
            return parent::getFinalTarget($uploadData);
        }

        // This part is replicated from the parent class to resolve the target dir
        $source = $uploadData['tmp_name'];
        $target = $this->getTarget();
        if (! isset($target) || $target == '*') {
            $target = $source;
        }

        // Get the target directory
        if (is_dir($target)) {
            $targetDir = $target;
            $last      = $target[strlen($target) - 1];
            if (($last != '/') && ($last != '\\')) {
                $targetDir .= DIRECTORY_SEPARATOR;
            }
        } else {
            $info      = pathinfo($target);
            $targetDir = $info['dirname'] . DIRECTORY_SEPARATOR;
        }

        return $callback($uploadData, $targetDir);
    }
}
