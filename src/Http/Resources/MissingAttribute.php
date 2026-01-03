<?php

namespace MustafaFares\SelectiveResponse\Http\Resources;

class MissingAttribute
{
    public function __call($method, $arguments)
    {
        return $this;
    }

    public function __toString(): string
    {
        return '';
    }
}

