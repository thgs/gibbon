<?php

namespace Gibbon\Renderers;

use Amp\Promise;
use Amp\File as AmpFile;

use function Amp\call;

class BasicRenderer // implements Renderer
{
    public function __construct(
        private string $appDir
    ) {
    }

    public function renderDirectory($path): Promise
    {
        return call(function () use ($path) {
            $isDirectory = yield AmpFile\isDirectory($path);
            if (!$isDirectory) {
                return '';
            }

            $directoryContents = yield AmpFile\listFiles($path);

            $data = '';
            foreach ($directoryContents as $entry) {
                if ($entry === '.gibbon') {
                    continue;
                }
                $isDirectory = yield AmpFile\isDirectory($path . '/' . $entry);
                $display = $isDirectory ? basename($entry) . '/' : basename($entry);

                // hacky map for directories below
                $displayPath = str_replace($this->appDir, '', $path) . '/' . $entry;

                $data .= "<a href=\"$displayPath\">$display</a><br />";
            }

            return $data;
        });
    }

    /**
     * @param string $file
     * @return Promise<string>
     */
    public function renderTxt(string $file): Promise
    {
        return call(function () use ($file) {
            $contents = yield AmpFile\read($file);
            return '<pre>' . $contents . '</pre>';
        });
    }

    public function renderPDF($file): Promise
    {
        // not implemented
        return call(function () {});
    }

    public function renderHTML($file): Promise
    {
        // not implemented
        return call(function () {});
    }

    public function renderPHP($file): Promise
    {
        // not implemented
        return call(function () {});
    }

    public function render($file, $data = null): Promise
    {
        return call(function () use ($file, $data) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            $response = match ($ext) {
                'txt' => yield $this->renderTxt($file),
                'html' => yield $this->renderHTML($file),
                'pdf' => yield $this->renderPDF($file),
                'php' => yield $this->renderPHP($file),
                default => yield $this->renderDirectory($file)
            };

            return $response;
        });
    }
}
