<?php

namespace Monki\Generator\Method;

class Pub
{
    public function __construct($name, array $arguments = [])
    {
        $this->name = $name;
        $this->arguments = $arguments;
        $this->body = '';
    }

    public function setBody($body = '')
    {
        $this->body = $body;
    }

    public function __toString()
    {
        $indent = '    ';
        $out = $indent."public function {$this->name}("
            .implode(', ', $this->arguments)
            .")\n";
        $out .= $indent."{\n";
        if (strlen($this->body)) {
            foreach (explode("\n", $this->body) as $line) {
                $out .= "$indent$indent$line\n";
            }
        }
        $out .= $indent."}\n";
        return $out;
    }
}

