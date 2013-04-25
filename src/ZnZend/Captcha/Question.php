<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Captcha;

use Zend\Captcha\AbstractWord;
use ZnZend\Captcha\Exception;

/**
 * Image-based captcha adapter for custom questions and answers
 *
 * Takes in custom question (eg. 'What is the color of the sky?') and answer (eg. 'blue')
 * and generates inline image for question.
 *
 * Bulk of code from Zend\Captcha\Image with the following differences:
 *   - No need for imgDir as inline image is generated
 *   - Width and height are automatically calculated to fit the text
 *   - Captcha question is supplied, not generated
 *   - isValid() checks against supplied answer, not question
 */
class Question extends AbstractWord
{
    /**
     * Question
     *
     * @var string
     */
    protected $question;

    /**
     * Answer
     *
     * @var string
     */
    protected $answer;

    /**
     * Inline image generated for captcha question
     *
     * @example "data:image\/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
     * @var string For use in HTML <img src=".." />
     */
    protected $inlineImage;

    /**
     * Font size
     *
     * @var int
     */
    protected $fontSize = 24;

    /**
     * Fully qualified path to image font file
     *
     * @var string
     */
    protected $font;

    /**
     * Padding (in pixels) around question in generated image
     *
     * @var int
     */
    protected $padding = 5;

    /**
     * Number of noise dots on image
     * Used twice - before and after transform
     *
     * @var int
     */
    protected $dotNoiseLevel = 100;

    /**
     * Number of noise lines on image
     * Used twice - before and after transform
     *
     * @var int
     */
    protected $lineNoiseLevel = 5;

    /**
     * Flag for transforming image (wave transforms)
     *
     * @var bool
     */
    protected $transformImage = true;

    /**
     * Constructor
     *
     * @param  array|\Traversable $options
     * @throws Exception\ExtensionNotLoadedException
     */
    public function __construct($options = null)
    {
        if (!extension_loaded('gd')) {
            throw new Exception\ExtensionNotLoadedException('Image CAPTCHA requires GD extension');
        }

        if (!function_exists("imagepng")) {
            throw new Exception\ExtensionNotLoadedException('Image CAPTCHA requires PNG support');
        }

        if (!function_exists("imageftbbox")) {
            throw new Exception\ExtensionNotLoadedException('Image CAPTCHA requires FreeType fonts support');
        }

        parent::__construct($options);
    }

    /**
     * Get form view helper name used to render captcha
     *
     * @return string
     */
    public function getHelperName()
    {
        return 'znZendFormCaptchaQuestion';
    }

    /**
     * Get captcha question
     *
     * @return string
     */
    public function getQuestion()
    {
        if (empty($this->question)) {
            $session        = $this->getSession();
            $this->question = $session->question;
        }
        return $this->question;
    }

    /**
     * Get captcha answer
     *
     * @return string
     */
    public function getAnswer()
    {
        if (empty($this->answer)) {
            $session      = $this->getSession();
            $this->answer = $session->answer;
        }
        return $this->answer;
    }

    /**
     * Get inline image generated for captcha question
     *
     * @return string
     */
    public function getInlineImage()
    {
        if (empty($this->inlineImage)) {
            $session     = $this->getSession();
            $this->inlineImage = $session->inlineImage;
        }
        return $this->inlineImage;
    }

    /**
     * Get padding
     *
     * @return int
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * Get font to use when generating captcha
     *
     * @return string
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Get font size
     *
     * @return int
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * @return int
     */
    public function getDotNoiseLevel()
    {
        return $this->dotNoiseLevel;
    }

    /**
     * @return int
     */
    public function getLineNoiseLevel()
    {
        return $this->lineNoiseLevel;
    }

    /**
     * @return bool
     */
    public function getTransformImage()
    {
        return $this->transformImage;
    }

    /**
     * Set captcha question
     *
     * @param  string $question
     * @return Question
     */
    public function setQuestion($question)
    {
        $session           = $this->getSession();
        $session->question = $question;
        $this->question    = $question;
        return $this;
    }

    /**
     * Set captcha answer
     *
     * @param  string $answer
     * @return Question
     */
    public function setAnswer($answer)
    {
        $session         = $this->getSession();
        $session->answer = $answer;
        $this->answer    = $answer;
        return $this;
    }

    /**
     * Set inline image generated for captcha question
     *
     * @param  string $inlineImage
     * @return Question
     */
    public function setInlineImage($inlineImage)
    {
        $session              = $this->getSession();
        $session->inlineImage = $inlineImage;
        $this->inlineImage    = $inlineImage;
        return $this;
    }

    /**
     * Set captcha font
     *
     * @param  string $font
     * @return Question
     */
    public function setFont($font)
    {
        $this->font = $font;
        return $this;
    }

    /**
     * Set captcha font size
     *
     * @param  int $fontSize
     * @return Question
     */
    public function setFontSize($fontSize)
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * Set padding
     *
     * @param  int $padding
     * @return Question
     */
    public function setPadding($padding)
    {
        $this->padding = $padding;
        return $this;
    }

    /**
     * @param int $dotNoiseLevel
     * @return Question
     */
    public function setDotNoiseLevel($dotNoiseLevel)
    {
        $this->dotNoiseLevel = $dotNoiseLevel;
        return $this;
    }

    /**
     * @param int $lineNoiseLevel
     * @return Question
     */
    public function setLineNoiseLevel($lineNoiseLevel)
    {
        $this->lineNoiseLevel = $lineNoiseLevel;
        return $this;
    }

    /*
     * @param bool $transformImage
     * @return Question
     */
    public function setTransformImage($transformImage)
    {
        $this->transformImage = $transformImage;
        return $this;
    }

    /**
     * Generate random frequency
     *
     * @return float
     */
    protected function randomFreq()
    {
        return mt_rand(700000, 1000000) / 15000000;
    }

    /**
     * Generate random phase
     *
     * @return float
     */
    protected function randomPhase()
    {
        // random phase from 0 to pi
        return mt_rand(0, 3141592) / 1000000;
    }

    /**
     * Generate random character size
     *
     * @return int
     */
    protected function randomSize()
    {
        return mt_rand(300, 700) / 100;
    }

    /**
     * Generate captcha
     *
     * @return string captcha ID
     */
    public function generate()
    {
        $id = $this->generateRandomId();
        $this->setId($id);
        $this->generateInlineImage($id, $this->getQuestion());

        return $id;
    }

    /**
     * Generate inline image captcha for question
     *
     * Image format is in PNG
     * Wave transform from http://www.captcha.ru/captchas/multiwave/
     *
     * @param  string $id Captcha ID
     * @param  string $word Captcha word
     * @return void
     * @throws Exception\NoFontProvidedException if no font was set
     * @throws Exception\ImageNotLoadableException if start image cannot be loaded
     */
    protected function generateInlineImage($id, $question)
    {
        $font = $this->getFont();

        if (empty($font)) {
            throw new Exception\NoFontProvidedException('Image CAPTCHA requires font');
        }

        // Retrieve bounding box and calculate text dimensions
        $fontSize    = $this->getFontSize();
        $padding     = $this->getPadding();
        $typeSpace   = imageftbbox($fontSize, 0, $font, $question);
        $width  = abs($typeSpace[4] - $typeSpace[0]) + (2 * $padding);
        $height = abs($typeSpace[5] - $typeSpace[1]) + (2 * $padding);

        // Create canvas
        $image      = imagecreatetruecolor($width, $height);
        $textColor  = imagecolorallocate($image, 0, 0, 0);
        $noiseColor = imagecolorallocate($image, 128, 128, 128);
        $bgColor    = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgColor);

        // Create text
        // $y is the font baseline, not the very bottom of the character, eg. "g", hence minus (1.5 * padding)
        $x = $padding;
        $y = $height - (1.5 * $padding);
        imagefttext($image, $fontSize, 0, $x, $y, $textColor, $font, $question);

        // Generate noise
        for ($i = 0; $i < $this->dotNoiseLevel; $i++) {
            imagefilledellipse($image, mt_rand(0, $width), mt_rand(0, $height), 2, 2, $textColor);
        }
        for ($i = 0; $i < $this->lineNoiseLevel; $i++) {
            imageline(
                $image,
                mt_rand(0, $width), mt_rand(0, $height),
                mt_rand(0, $width), mt_rand(0, $height),
                $textColor
            );
        }

        // Generate inline image if there is no need to transform
        if (!$this->transformImage) {
            // Capture image output
            ob_start();
            imagepng($image);
            imagedestroy($image);
            $imageData = ob_get_contents();
            ob_end_clean();

            $this->setInlineImage('data:image/png;base64,' . base64_encode($imageData));
            return;
        }

        // Transformed image
        $transformedImage = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($transformedImage, 255, 255, 255);
        imagefilledrectangle($transformedImage, 0, 0, $width - 1, $height - 1, $bgColor);

        // Apply wave transforms
        $freq1 = $this->randomFreq();
        $freq2 = $this->randomFreq();
        $freq3 = $this->randomFreq();
        $freq4 = $this->randomFreq();

        $ph1 = $this->randomPhase();
        $ph2 = $this->randomPhase();
        $ph3 = $this->randomPhase();
        $ph4 = $this->randomPhase();

        $szx = $this->randomSize();
        $szy = $this->randomSize();

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $sx = $x + (sin($x*$freq1 + $ph1) + sin($y*$freq3 + $ph3)) * $szx;
                $sy = $y + (sin($x*$freq2 + $ph2) + sin($y*$freq4 + $ph4)) * $szy;

                if ($sx < 0 || $sy < 0 || $sx >= $width - 1 || $sy >= $height - 1) {
                    continue;
                } else {
                    $color   = (imagecolorat($image, $sx, $sy) >> 16)         & 0xFF;
                    $colorX  = (imagecolorat($image, $sx + 1, $sy) >> 16)     & 0xFF;
                    $colorY  = (imagecolorat($image, $sx, $sy + 1) >> 16)     & 0xFF;
                    $colorXY = (imagecolorat($image, $sx + 1, $sy + 1) >> 16) & 0xFF;
                }

                if ($color == 255 && $colorX == 255 && $colorY == 255 && $colorXY == 255) {
                    // ignore background
                    continue;
                } elseif ($color == 0 && $colorX == 0 && $colorY == 0 && $colorXY == 0) {
                    // transfer inside of the image as-is
                    $newcolor = 0;
                } else {
                    // do anti-aliasing for border items
                    $fracX  = $sx - floor($sx);
                    $fracY  = $sy - floor($sy);
                    $fracX1 = 1 - $fracX;
                    $fracY1 = 1 - $fracY;

                    $newcolor = $color   * $fracX1 * $fracY1
                              + $colorX  * $fracX  * $fracY1
                              + $colorY  * $fracX1 * $fracY
                              + $colorXY * $fracX  * $fracY;
                }

                imagesetpixel(
                    $transformedImage, $x, $y, imagecolorallocate($transformedImage, $newcolor, $newcolor, $newcolor)
                );
            }
        }

        // Generate noise for transformed image
        for ($i = 0; $i < $this->dotNoiseLevel; $i++) {
            imagefilledellipse($transformedImage, mt_rand(0, $width), mt_rand(0, $height), 2, 2, $textColor);
        }
        for ($i = 0; $i < $this->lineNoiseLevel; $i++) {
            imageline(
                $transformedImage,
                mt_rand(0, $width), mt_rand(0, $height),
                mt_rand(0, $width), mt_rand(0, $height),
                $textColor
            );
        }

        // Capture image output
        ob_start();
        imagepng($transformedImage);
        imagedestroy($image);
        imagedestroy($transformedImage);
        $imageData = ob_get_contents();
        ob_end_clean();

        $this->setInlineImage('data:image/png;base64,' . base64_encode($imageData));
    }

    /**
     * Validate the question
     *
     * Exact code as \Zend\Captcha\AbstractWord::isValid() except for last part
     * where it checks against the answer instead of the word/question
     *
     * @see    \Zend\Validator\ValidatorInterface::isValid()
     * @param  mixed $value
     * @param  mixed $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        if (!is_array($value)) {
            if (!is_array($context)) {
                $this->error(self::MISSING_VALUE);
                return false;
            }
            $value = $context;
        }

        $name = $this->getName();

        if (isset($value[$name])) {
            $value = $value[$name];
        }

        if (!isset($value['input'])) {
            $this->error(self::MISSING_VALUE);
            return false;
        }
        $input = strtolower($value['input']);
        $this->setValue($input);

        if (!isset($value['id'])) {
            $this->error(self::MISSING_ID);
            return false;
        }

        $this->id = $value['id'];
        if ($input !== $this->getAnswer()) {
            $this->error(self::BAD_CAPTCHA);
            return false;
        }

        return true;
    }
}
