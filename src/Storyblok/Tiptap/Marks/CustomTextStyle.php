<?php

namespace TAFER\Core\Storyblok\Tiptap\Marks;

use Tiptap\Marks\TextStyle;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class CustomTextStyle extends TextStyle
{
    public function addAttributes()
    {
        return [
            'color' => [
                'default' => null,
                'parseHTML' => function ($DOMNode) {
                    return $DOMNode->style->color ?? null;
                },
                'renderHTML' => function ($attributes) {
                    // Tiptap puede pasar $attributes como objeto stdClass o array
                    $color = is_object($attributes) ? ($attributes->color ?? null) : ($attributes['color'] ?? null);

                    if (! $color) {
                        return null;
                    }

                    return [
                        'class' => "text-{$color}",
                    ];
                },
            ],
        ];
    }
}
