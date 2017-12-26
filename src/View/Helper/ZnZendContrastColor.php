<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Choose color that provides sufficient constrast when combined with specified color
 *
 * Limited to black and white for now.
 */
class ZnZendContrastColor extends AbstractHelper
{
    /**
     * Available contrast colors and their component values
     *
     * @var array $colorCode => array('red' => $redValue, 'green' => $greenValue, 'blue' => $blueValue)
     */
    protected $contrastColors = array(
        '#000000' => array('red' => 0, 'green' => 0, 'blue' => 0), // black
        '#ffffff' => array('red' => 255, 'green' => 255, 'blue' => 255), // white
    );

    /**
     * Algorithms for computing difference between 2 colors
     *
     * @see http://www.w3.org/TR/AERT for algorithm under Checkpoint 2.2.1
     * @var array $name => array('method' => $methodName, 'threshold' => $thresholdValue)
     */
    protected $algorithms = array(
        'brightness' => array('method' => 'brightnessDifference', 'threshold' => 125),
        'color' => array('method' => 'colorDifference', 'threshold' => 500),
    );

    /**
     * __invoke; Choose color that provides sufficient constrast when combined with specified color
     *
     * @param  string $color     Hexadecimal color code, eg. #ff00ff
     * @param  string $algorithm Optional algorithm listed under $algorithms
     * @return string One of the keys listed under $contrastColors
     */
    public function __invoke($color, $algorithm = 'brightness')
    {
        // Get method and threshold for algorithm
        if (!isset($this->algorithms[$algorithm])) {
            $algorithmKeys = array_keys($this->algorithms);
            $algorithm = reset($algorithmKeys);
        }
        $method = $this->algorithms[$algorithm]['method'];
        $threshold = $this->algorithms[$algorithm]['threshold'];

        // Normalize color code
        $color = ltrim($color, '#');
        if (3 == strlen($color)) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        $colorValues = array(
            'red'   => hexdec(substr($color, 0, 2)),
            'green' => hexdec(substr($color, 2, 2)),
            'blue'  => hexdec(substr($color, 4, 2)),
        );

        // Perform calculations and return the first contrast color that meets/exceeds the threshold
        $differences = array();
        foreach ($this->contrastColors as $contrastColor => $contrastValues) {
            $value = $this->$method($colorValues, $contrastValues);
            if ($value >= $threshold) {
                return $contrastColor;
            }
            $differences[$contrastColor] = $value;
        }

        // In the event none of the contrast colors meet the threshold, choose the one with the highest value
        arsort($differences);
        $diffKeys = array_keys($differences);
        return reset($diffKeys);
    }

    /**
     * Calculate brightness difference
     *
     * @param  array $color1 array('red' => $redValue, 'green' => $greenValue, 'blue' => $blueValue)
     * @param  array $color2 array('red' => $redValue, 'green' => $greenValue, 'blue' => $blueValue)
     * @return int|float
     */
    protected function brightnessDifference(array $color1, array $color2)
    {
        $value1 = (($color1['red'] * 299) + ($color1['green'] * 587) + ($color1['blue'] * 114)) / 1000;
        $value2 = (($color2['red'] * 299) + ($color2['green'] * 587) + ($color2['blue'] * 114)) / 1000;
        return abs($value1 - $value2);
    }

    /**
     * Calculate color difference
     *
     * @param  array $color1 array('red' => $redValue, 'green' => $greenValue, 'blue' => $blueValue)
     * @param  array $color2 array('red' => $redValue, 'green' => $greenValue, 'blue' => $blueValue)
     * @return int|float
     */
    protected function colorDifference(array $color1, array $color2)
    {
        $difference = 0;
        foreach (array('red', 'green', 'blue') as $component) {
            $difference += (
                max($color1[$component], $color2[$component]) - min($color1[$component], $color2[$component])
            );
        }
        return $difference;
    }
}
