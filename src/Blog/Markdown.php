<?php

namespace BootPress\Blog;

use Aptoma\Twig\Extension\MarkdownEngineInterface;

class Markdown implements MarkdownEngineInterface
{
    /**
     * @var \BootPress\Blog\Theme
     */
    private $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($content)
    {
        return $this->theme->markdown($content);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Blog\Markdown';
    }
}
