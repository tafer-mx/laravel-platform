<?php

namespace TAFER\Core\Storyblok\Tiptap\Nodes;

use Tiptap\Nodes\Heading;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class CustomHeading extends Heading
{
    public function addAttributes()
    {
        return [
            'level' => [
                'default' => 1,
                'rendered' => false,
            ],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = [])
    {
        $level = $node->attrs->level ?? 1;

        // No se agregan clases por defecto
        return ["h{$level}", $HTMLAttributes, 0];
    }
}
