<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated when booting a builder.
 */
class AdaptBootException extends AdaptException
{
    /**
     * Thrown when a no PropBag was set when booting a builder.
     *
     * @return self
     */
    public static function propBagNotSet(): self
    {
        return new self('No prob-bag was set');
    }
}
