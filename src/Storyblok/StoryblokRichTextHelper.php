<?php

namespace TAFER\Core\Storyblok;

use Storyblok\Tiptap\Extension\Storyblok;
use Storyblok\Tiptap\Node\BulletList;
use Storyblok\Tiptap\Node\OrderedList;
use TAFER\Core\Storyblok\Tiptap\Marks\CustomLink;
use TAFER\Core\Storyblok\Tiptap\Marks\CustomTextStyle;
use TAFER\Core\Storyblok\Tiptap\Nodes\CustomHeading;
use Tiptap\Editor;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class StoryblokRichTextHelper
{
    /**
     * Render Storyblok Rich Text to HTML using Tiptap.
     *
     * @param  array|null  $node
     */
    public static function render($node): string
    {
        if (! is_array($node)) {
            return '';
        }

        $editor = new Editor([
            'extensions' => [
                new Storyblok([
                    'override_extensions' => [
                        CustomLink::$name => new CustomLink,
                    ],
                    'disable_extensions' => ['bullet_list', 'heading', 'ordered_list', 'blok'],
                ]),
                new CustomTextStyle,
                new CustomHeading,
                new BulletList([
                    'HTMLAttributes' => [
                        'class' => 'list-disc list-outside ml-6 mt-4 mb-4 space-y-2',
                    ],
                ]),
                new OrderedList([
                    'HTMLAttributes' => [
                        'class' => 'list-decimal list-outside ml-6 mt-4 mb-4 space-y-2',
                    ],
                ]),
            ],
        ]);

        $editor->setContent($node);

        return $editor->getHTML();
    }
}
