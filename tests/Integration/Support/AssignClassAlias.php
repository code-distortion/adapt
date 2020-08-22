<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

use Orchestra\Testbench\TestCase;

/**
 * Detect the relevant TestCase setUp() method signature to use, and create an alias for the relevant trait.
 *
 * TestCase's setUp() method has different signatures in different versions of Laravel. This code checks which
 * version is currently being used and uses the appropriate setUp() method to match.
 */
class AssignClassAlias
{
    /**
     * Choose the correct setUp() signature to use.
     *
     * @param string $namespace The namespace to import DatabaseBuilderSetUpTrait into.
     * @return void
     */
    public static function databaseBuilderSetUpTrait(string $namespace): void
    {
        // only perform this once
        $destTrait = $namespace.'\\DatabaseBuilderSetUpTrait';
        if (trait_exists($destTrait)) {
            return;
        }

        $sourceTrait = (static::setUpReturnsVoid()
            ? DatabaseBuilderSetUpVoidTrait::class
            : DatabaseBuilderSetUpNoVoidTrait::class
        );
        class_alias($sourceTrait, $destTrait);
    }

    /**
     * Check to see if the TestCase::setUp() method returns void.
     *
     * @return boolean
     */
    private static function setUpReturnsVoid(): bool
    {
        $setupMethod = new \ReflectionMethod(TestCase::class, 'setUp');
        return (($setupMethod->getReturnType()) && ($setupMethod->getReturnType()->getName() == 'void'));
    }
}
