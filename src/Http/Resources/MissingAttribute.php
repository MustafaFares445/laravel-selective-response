<?php

namespace MustafaFares\SelectiveResponse\Http\Resources;

use JsonSerializable;

class MissingAttribute implements JsonSerializable
{
    public function __call($method, $arguments)
    {
        return $this;
    }

    public function __toString(): string
    {
        return '';
    }

    public function jsonSerialize(): mixed
    {
        return null;
    }
}

