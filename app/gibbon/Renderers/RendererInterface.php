<?php

namespace Gibbon\Renderers;

use Amp\Promise;

interface RendererInterface
{
    /**
     * @param string $file
     * @return Promise<string>
     */
    public function render($file): Promise;
}
