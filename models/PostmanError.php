<?php

/**
 * Class PostmanError
 */
class PostmanError
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $content;

    /**
     * PostmanError constructor.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->message = $data['post_excerpt'] . ' on ' . site_url();
        $this->subject = $data['post_title'];
        $this->content = $data['post_content'];
    }
}