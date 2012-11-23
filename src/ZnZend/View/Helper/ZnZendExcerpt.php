<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-11-23T23:00+08:00
 */
namespace ZnZend\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper to extract excerpt from text using More tag or specified word count
 */
class ZnZendExcerpt extends AbstractHelper
{
    /**
     * More tag
     * @see http://en.support.wordpress.com/splitting-content/more-tag/
     * @var string
     */
    protected $moreTag = '<!--more-->';

    /**
     * __invoke
     *
     * Text is cut off before More tag if it exists or before specified word count
     * Note that strip_tags() is applied on text if the latter happens
     *
     * @param  string $text       Input text
     * @param  int    $maxWords   DEFAULT=0. Number of words to cut off at if more tag is not found
     * @param  string $moreText   DEFAULT=''. Text to append to excerpt, eg. '...Read more'
     * @param  string $moreLink   DEFAULT=''. Url for $moreText. If empty, there will be no link
     * @param  string $moreTarget DEFAULT=''. Target for $moreLink, eg. '_blank'
     */
    public function __invoke($text, $maxWords = 0, $moreText = '', $moreLink = '', $moreTarget = '')
    {
        if (empty($text)) {
            return $text;
        }

        // Check for More tag
        $morePos = stripos($text, $this->moreTag);
        if ($morePos !== false) {
            $excerpt = rtrim(substr($text, 0, $morePos)); // remove trailing space if any
        } else {
            // Truncate by word count
            $maxWords = (int) $maxWords;
            $text = strip_tags($text); // remove HTML tags

            $token = " \t\r\n";
            $excerpt = strtok($text, $token);
            while (--$maxWords > 0) {
                $excerpt .= ' ' . strtok($token);
            }
        }

        if ($excerpt == $text) {
            return $text;
        }

        if (empty($moreLink)) {
            return $excerpt . ' ' . $moreText;
        }

        return sprintf(
            '%s <a target="%s" href="%s">%s</a>',
            $excerpt,
            $moreTarget,
            $moreLink,
            $moreText
        );
    } // end function __invoke

} // end class
