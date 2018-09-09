<?php

namespace Gibbon\Renderers;

use Michelf\Markdown;
use Michelf\MarkdownExtra;

class MDRenderer
{
    public function __construct($extra = null)
    {
        $this->extra = $extra;
    }

    public function render($file, $data = null)
    {
        if ($data == null) {
            $data = file_get_contents($file);
        }

        if ($this->extra) {
            return MarkdownExtra::defaultTransform($data);
        } else {
            return Markdown::defaultTransform($data);
        }
    }
}
