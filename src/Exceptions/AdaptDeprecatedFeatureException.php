<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated when deprecated features are used.
 */
class AdaptDeprecatedFeatureException extends AdaptException
{
    /**
     * Let the caller know that they've used a deprecated feature.
     *
     * @param string      $deprecated  The deprecated feature used.
     * @param string|null $alternative An alternative that could be used instead.
     * @return self
     */
    public static function deprecatedFeatureUsed(string $deprecated, ?string $alternative = null): self
    {
        return new self(
            $alternative
                ? "\"$deprecated\" has been deprecated. Please use \"$alternative\" instead"
                : "\"$deprecated\" has been deprecated"
        );
    }
}
