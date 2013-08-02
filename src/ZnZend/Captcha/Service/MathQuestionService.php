<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Captcha\Service;

use ZnZend\Captcha\Service\QuestionServiceInterface;

/**
 * Generate simple addition/subtraction question for ZnZend\Captcha\Question adapter
 */
class MathQuestionService implements QuestionServiceInterface
{
    /**
     * Current question
     *
     * @var string
     */
    protected $question;

    /**
     * Answer to current question
     *
     * @var mixed
     */
    protected $answer;

    /**
     * Map operator to English text
     *
     * @var array
     */
    protected $operators = array(self::ADD => 'plus', self::SUBTRACT => 'minus');

    /**
     * Operator constants
     */
    const ADD = 1;
    const SUBTRACT = -1;

    /**
     * Defined by QuestionServiceInterface; Generate new set of question and answer
     *
     * @return MathQuestionService
     */
    public function generate()
    {
        $operand1 = mt_rand(10, 100);
        $operand2 = mt_rand(1, 9);
        $operator = array_rand($this->operators);
        $answer   = $operand1 + ($operator * $operand2);

        $question = sprintf(
            '%s %s %s',
            $this->translate($operand1),
            $this->operators[$operator],
            $this->translate($operand2)
        );

        $this->question = $question;
        $this->answer = $answer;
    }

    /**
     * Defined by QuestionServiceInterface; Get current question
     *
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Defined by QuestionServiceInterface; Verify user input with current answer
     *
     * @param  mixed $value
     * @return bool
     */
    public function verify($value)
    {
        return ($value == $this->answer);
    }

    protected function separateThousands($num)
    {
        // separate a number by thousands using comma - meant for display only
        $ans = '';

        while (strlen($num) > 3) {
            $ans = ', ' . substr($num, -3, 3) . $ans; // take the last 3 digits
            $num = substr($num, 0, -3); //cut away last 3 digits
        }

        $ans = $num . $ans;
        return $ans;
    }

    protected function translate($num)
    {
        // Translates a number into words, from 0 to 999 999 999.
        // Eg: 264 073 458 = two hundred and sixty-four million, seventy-three thousand, four hundred and fifty-eight
        $num = (int) $num;

        // special case
        if (0 == $num) {
            return 'zero';
        }

        // index 0 not used. Non-empty place suffixes have ' ' in front to facilitate concatenation
        $placeSuffix = array('', '', '', ' hundred', ' thousand', ' thousand', ' thousand', ' million', ' million', ' million');
        $onesPrefix  = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine');
        $teensPrefix = array('ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen');
        $tens        = array('', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety');

        $ans = '';
        $partAns = '';

        while (strlen($num) > 0) {
            $digit  = (int) substr($num, 0, 1);
            $place  = strlen($num);
            $prefix = '';

            // parse number according to pronunciation types
            if (in_array($place, array(6, 9))) { // 100 thousand, 100 million
                $function = __FUNCTION__;
                $prefix = $onesPrefix[$digit] . ' hundred';
                $next2digits = $this->$function(substr($num, 1, 2)); // process the next 2 digits
                if ($next2digits != 'zero') {
                    $prefix .= ' and ' . $next2digits;
                }
                $partAns = $prefix . $placeSuffix[$place];
                $num = substr($num, 3); // cut off the 3 leftmost digits
            } elseif (in_array($place, array(2, 5, 8))) { // 10, 10 thousand, 10 million
                $nextdigit = (int) substr($num, 1, 1);

                if (1 == $digit) {  // 10 to 19
                    $prefix = $teensPrefix[$nextdigit];
                } elseif ($digit > 1) {
                    $prefix = $tens[$digit];
                    if ($nextdigit != 0) {
                        $prefix = $tens[$digit] . '-' . $onesPrefix[$nextdigit];
                    }
                } else {
                    $prefix = '';
                }
                $partAns = $prefix . $placeSuffix[$place];
                $num = substr($num, 2); // cut off the 2 leftmost digits
            } elseif (in_array($place, array(1, 3, 4, 7))) { // 1, 1 hundred, 1 thousand, 1 million
                $prefix   = $onesPrefix[$digit];
                $partAns = $prefix . $placeSuffix[$place];
                $num = substr($num, 1); // cut off the leftmost digit
            }

            // Eliminate the redundant zeroes in front
            if ($num != '') {
                while ('0' == substr($num, 0, 1)) {
                    $num = substr($num, 1);
                }
            }

            // Concatenate the new part to the whole answer
            if ('' == $ans) {
                $ans .= $partAns;
            } elseif (strlen($num) > 0) {
                $ans .= ', ' . $partAns;
            } else {
                $ans .= ' and ' . $partAns;
            }

        } //end while

        return $ans;
    }
}
