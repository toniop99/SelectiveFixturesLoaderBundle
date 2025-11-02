<?php

namespace Andez\SelectiveFixturesLoaderBundle;

interface BaseFixturesLoaderInterface
{
    /**
     * Returns an array of base fixture class names.
     *
     * @return class-string[]
     */
    public function getBaseFixtures(): array;
}
