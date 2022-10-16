<?php

namespace Gibbon\Renderers;

use Amp\Promise;
use Amp\File as AmpFile;
use Michelf\Markdown;
use Michelf\MarkdownExtra;

use function Amp\call;

class MDRenderer
{
    public function __construct($extra = null)
    {
        $this->extra = $extra;
    }

    public function render($file, $data = null): Promise
    {
        return call(function () use ($file, $data) {
            if ($data == null) {
                $data = yield AmpFile\read($file);
            }

            if ($this->extra) {
                return MarkdownExtra::defaultTransform($data);
            } else {
                return Markdown::defaultTransform($data);
            }
        });
    }
}
