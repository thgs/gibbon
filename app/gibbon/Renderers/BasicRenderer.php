<?php

namespace Gibbon\Renderers;

class BasicRenderer // implements Renderer
{
    public function renderDirectory($file)
    {
        $directoryFiles = scandir($file);               // this shouldn't be here
        $data = '';
        foreach ($directoryFiles as $f) {
            $b = basename($f);
            if (is_dir($f)) {
                $data .= '<a href="' . $b . '">' . $b . '/</a><br />';
            } else {
                $data .= '<a href="' . $b . '">' . $b . '</a><br />';
            }
        }

        return $data;
    }

    public function renderTxt($file)
    {
        return '<pre>' . file_get_contents($file) . '</pre>';
    }

    public function renderPDF($file)
    {
    }

    public function renderHTML($file)
    {
    }

    public function renderPHP($file)
    {
    }

    public function render($file, $data = null)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        switch ($ext) {
            case 'txt':     return $this->renderTxt($file);
            case 'html':    return $this->renderHTML($file);
            case 'pdf':     return $this->renderPDF($file);
            case 'php':     return $this->renderPHP($file);
            default:        return $this->renderDirectory($file);
        }
    }
}
