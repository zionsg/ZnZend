<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\View\Helper;

use Zend\View\Helper\AbstractHelper;
use ZnZend\View\Exception;

/**
 * Output entities in columns
 */
class ZnZendColumnizeEntities extends AbstractHelper
{
    /**
     * __invoke
     *
     * This is used as a proxy as columnize() has other arguments meant for
     * its internal use only
     *
     * @params array @see columnize() for docblock on $params
     * @return string
     */
    public function __invoke(array $params = array())
    {
        return $this->columnize($params);
    }

    /**
     * Iterative function to columnize entities
     *
     * Result can be formatted using CSS classes
     *
     * By default, for each entity, the output is as follows:
     *   <a href="$url">$thumbnail</a><div><a href="$url">$name</a></div>
     * If another format is desired, use $entityCallback
     *
     * @param  array $params Key-value pairs as follows:
     *         'cols'           int      DEFAULT=1. No. of columns to split entities in
     *         'drawTable'      boolean  DEFAULT=true. Whether to enclose all entities in a table with
     *                                   1 entity per cell. Sometimes the user may only want to process
     *                                   1 entity in which case the outermost table is not needed
     *         'entities'       object[] Array of entity objects. Single object may be passed
     *         'entityCallback' callback Callback function that takes in entity and returns formatted
     *                                   HTML for entity. If this is not defined, the default format
     *                                   of url, thumbnail and name is used
     *         'nameClass'      string   CSS class for entity name
     *         'nameCallback'   callback Callback function that takes in entity and returns name
     *         'leftToRight'    boolean  DEFAULT=true. Whether to list entities from left to right
     *                                   or top to down. Examples with $remainderAlign set to 'center'
     *                                       Left to right
     *                                       1   2   3
     *                                       4   5   6
     *                                         7   8
     *
     *                                       Top to down
     *                                       1   3   5
     *                                       2   4   6
     *                                         7   8
     *         'remainderAlign' string   DEFAULT='center'. How to align the remainder entities in
     *                                   the last row. Possible values: left, center.
     *         'tableClass'     string   CSS class for entire table enclosing all entities
     *         'tableId'        string   'id' attribute for entire table enclosing all entities,
     *                                   to facilitate DOM reference
     *         'tdClass'        string   CSS class for <td> enclosing individual entity
     *         'trClass'        string   CSS class for <tr> enclosing individual entity <td>
     *         'urlCallback'    callback Callback function that takes in entity and returns entity url
     *         'urlClass'       string   CSS class for entity url
     *         'urlTarget'      string   Target for entity url. <a target="urlTarget"...
     *
     *         Keys for drawing thumbnail images:
     *         'drawThumbnailBox'   boolean  DEFAULT=true. Whether to enclose thumbnail <img> in <td>.
     *                                       If true, box will be drawn even if there's no thumbnail
     *         'thumbnailBoxClass'  string   CSS class for <td> box enclosing thumbnail image
     *         'thumbnailClass'     string   CSS class for thumbnail image
     *         'thumbnailCallback'  callback Callback function that takes in entity and returns
     *                                       thumbnail filename
     *         'thumbnailPath'      string   Folder path relative to web root where thumbnail is stored
     *         'maxThumbnailHeight' int      DEFAULT=0. Maximum height constraint for thumbnail image
     *                                       If set to 0, "height" attribute will be skipped in output
     *         'maxThumbnailWidth'  int      DEFAULT=0. Maximum width constraint for thumbnail image
     *                                       If set to 0, "width" attribute will be skipped in output
     *         'webRoot'            string   Absolute path for web root. Used for retrieving thumbnail.
     *                                       If thumbnail is a remote image, eg. http://test.com/test.png,
     *                                       set webRoot to '' and thumbnailPath to 'http://test.com'
     * @param  string $output For internal use during iteration. Stores final output
     * @return string
     * @throws Exception\InvalidArgumentException When any of the callbacks is not callable
     */
    public function columnize(array $params = array(), $output = '')
    {
        // Ensure all keys are set before extracting to prevent notices
        $params = array_merge(
            array(
                'cols' => 1,
                'drawTable' => true,
                'entities' => array(),
                'entityCallback' => null,
                'nameClass' => '',
                'nameCallback' => null,
                'leftToRight' => true,
                'remainderAlign' => 'center',
                'tableClass' => '',
                'tableId' => '',
                'tdClass' => '',
                'trClass' => '',
                'urlCallback' => null,
                'urlClass' => '',
                'urlTarget' => '',
                // keys for drawing thumbnails
                'drawThumbnailBox' => true,
                'thumbnailBoxClass' => '',
                'thumbnailCallback' => null,
                'thumbnailClass' => '',
                'thumbnailPath' => '',
                'maxThumbnailHeight' => 0,
                'maxThumbnailWidth' => 0,
                'webRoot' => '',
            ),
            $params
        );
        extract($params);

        // Add trailing slash if not empty - simplifies joining of paths later
        $webRoot = $this->addSlash($webRoot);
        $thumbnailPath = $this->addSlash($thumbnailPath);

        // Convert associative array to numerically indexed array
        if (empty($entities)) {
            return $output;
        }
        $entities = array_values(
            is_array($entities) ? $entities : array($entities)
        );
        $entityCount = count($entities);

        // Calculate initial rows
        if (!in_array($remainderAlign, array('left', 'center'))) {
            $remainderAlign = 'center';
        }
        if ($remainderAlign == 'left') {
            $initialRows = (int) ceil($entityCount / $cols);
        } elseif ($remainderAlign == 'center') {
            $cols = min($cols, $entityCount);
            $initialRows = (int) floor($entityCount / $cols);
        }
        $tdWidth = 100 / $cols;
        $entitiesProcessed = 0;

        // Process entities and generate output
        if ($drawTable) {
            $output .= sprintf(
                '<table id="%s" class="%s" cellspacing="0" cellpadding="0" width="100%%">' . PHP_EOL,
                $tableId,
                $tableClass
            );
        }

        for ($row = 0; $row < $initialRows; $row++) {
            if ($drawTable) {
                $output .= sprintf('<tr class="%s">' . PHP_EOL, $trClass);
            }

            for ($col = 0; $col < $cols; $col++) {
                if ($drawTable) {
                    $output .= sprintf('<td class="%s" width="%d%%">' . PHP_EOL, $tdClass, $tdWidth);
                }

                // Get entity, depending on listing order (left-right or top-down)
                if ($leftToRight) {
                    $index = ($row * $cols) + $col;
                } else {
                    $index = ($col * $initialRows) + $row;
                }
                if ($index >= $entityCount) {
                    $output .= '</td>' . PHP_EOL; // remember to close td
                    continue;
                }
                $entity = $entities[$index];

                // Get entity output
                $entityOutput = '';

                if ($entityCallback) {
                    if (!is_callable($entityCallback)) {
                        throw new Exception\InvalidArgumentException('Invalid entity callback provided');
                    }
                    $entityOutput = $entityCallback($entity) . PHP_EOL;
                } else {
                    // Get entity url
                    $urlOutputBegin = '';
                    $urlOutputEnd = '';
                    if ($urlCallback) {
                        if (!is_callable($urlCallback)) {
                            throw new Exception\InvalidArgumentException('Invalid url callback provided');
                        }
                        $url = $urlCallback($entity);
                        $urlOutputBegin = sprintf(
                            '<a class="%s" target="%s" href="%s">' . PHP_EOL,
                            $urlClass,
                            $urlTarget,
                            $url
                        );
                        $urlOutputEnd = '</a>' . PHP_EOL;
                    }

                    // Get entity thumbnail
                    $thumbnail = null;
                    if ($thumbnailCallback) {
                        if (!is_callable($thumbnailCallback)) {
                            throw new Exception\InvalidArgumentException('Invalid thumbnail callback provided');
                        }
                        $thumbnail = $thumbnailCallback($entity);
                    }

                    // Draw thumbnail
                    if ($thumbnail !== null) {
                        $thumbnailOutput = '';
                        $imagePath = $webRoot . $thumbnailPath . $thumbnail;
                        $imageInfo = getimagesize($imagePath);
                        if (false !== $imageInfo) {
                            list($width, $height, $type, $attr) = $imageInfo;

                            if ($maxThumbnailWidth != 0 && $width > $maxThumbnailWidth) {
                                $height = ($height / $width) * $maxThumbnailWidth;
                                $width  = $maxThumbnailWidth;
                            }

                            if ($maxThumbnailHeight != 0 && $height > $maxThumbnailHeight) {
                                $width  = ($width / $height) * $maxThumbnailHeight;
                                $height = $maxThumbnailHeight;
                            }

                            $thumbnailOutput = sprintf(
                                '%s<img class="%s" align="center" src="%s" %s %s />' . PHP_EOL . '%s',
                                $urlOutputBegin,
                                $thumbnailClass,
                                $thumbnailPath . $thumbnail,
                                ($maxThumbnailWidth == 0 ? '' : "width=\"{$width}\""),
                                ($maxThumbnailHeight == 0 ? '' : "height=\"{$height}\""),
                                $urlOutputEnd
                            );
                        } // end if thumbnail file exists

                        // If true, box will be drawn even if there is no thumbnail
                        if ($drawThumbnailBox) {
                            $thumbnailOutput = sprintf(
                                '<table align="center" cellspacing="0" cellpadding="0">' . PHP_EOL
                                . '<tr><td class="%s" %s %s align="center" valign="middle">' . PHP_EOL
                                . '%s'
                                . '</td></tr>' . PHP_EOL
                                . '</table>' . PHP_EOL,
                                $thumbnailBoxClass,
                                ($maxThumbnailWidth == 0 ? '' : "width=\"{$maxThumbnailWidth}\""),
                                ($maxThumbnailHeight == 0 ? '' : "height=\"{$maxThumbnailHeight}\""),
                                $thumbnailOutput
                            );
                        }

                        // Add thumbnail output to entity output
                        $entityOutput .= $thumbnailOutput;
                    } // end draw thumbnail

                    // Get entity name and add to entity output
                    if ($nameCallback) {
                        if (!is_callable($nameCallback)) {
                            throw new Exception\InvalidArgumentException('Invalid name callback provided');
                        }
                        $name = $nameCallback($entity) . PHP_EOL;
                        $entityOutput .= sprintf(
                            '<div class="%s">%s%s%s</div>' . PHP_EOL,
                            $nameClass,
                            $urlOutputBegin,
                            $name,
                            $urlOutputEnd
                        );
                    }

                } // end entity output

                if ($drawTable) {
                    $output .= $entityOutput . '</td>' . PHP_EOL;
                } else {
                    $output .= $entityOutput . PHP_EOL;
                }

                $entitiesProcessed++;
            } // end for cols

            if ($drawTable) {
                $output .= '</tr>' . PHP_EOL;
            }
        } // end for rows

        if ($drawTable) {
            $output .= '</table>' . PHP_EOL;
        }

        // Call function again to output remaining entities
        $remainderCount = $entityCount % $cols;
        if ($remainderCount == 0) {
            return $output;
        } else {
            $remainderEntities = array();
            for ($i = $entitiesProcessed; $i < $entityCount; $i++) {
                $remainderEntities[] = $entities[$i];
            }
            $params['cols'] = $remainderCount;
            $params['entities'] = $remainderEntities;

            // No need to change code here if method name is changed
            $function = __FUNCTION__;
            return $this->$function($params, $output);
        }
    } // end function columnize

    /**
     * Adds trailing slash to path if not empty
     *
     * If path is empty, '' is returned
     * Simplifies joining of paths
     *
     * @param  string $path
     * @return string
     */
    protected function addSlash($path)
    {
        return (empty($path) ? '' : rtrim($path, "\\/") . '/');
    }
}
