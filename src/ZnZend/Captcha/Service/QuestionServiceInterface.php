<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Captcha\Service;

/**
 * Interface for services providing question/answer for ZnZend\Captcha\Question adapter
 */
interface QuestionServiceInterface
{
    /**
     * Generate new set of question and answer
     *
     * @return QuestionServiceInterface
     */
    public function generate();

    /**
     * Get current question
     *
     * @return string
     */
    public function getQuestion();

    /**
     * Get answer to current question
     *
     * @return string
     */
    public function getAnswer();
}
