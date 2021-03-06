<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Rocketeer\Binaries\PackageManagers;

use Rocketeer\TestCases\RocketeerTestCase;

class AbstractPackageManagerTest extends RocketeerTestCase
{
    public function testCanGetManifestPath()
    {
        $composer = $this->bash->composer();

        $this->assertEquals($this->container->get('path.base').'/'.$composer->getManifest(), $composer->getManifestPath());
    }
}
