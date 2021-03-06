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

namespace Rocketeer\Services\Storages;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use Rocketeer\Container;
use Rocketeer\Services\Config\Configuration;
use Rocketeer\Services\Config\ContextualConfiguration;
use Rocketeer\TestCases\RocketeerTestCase;

class StorageTest extends RocketeerTestCase
{
    public function testCanInferStorageName()
    {
        $this->container = new Container();
        $this->container->add('path.base', $this->server);
        $this->container->add(ContextualConfiguration::class, new Configuration([
            'config' => [
                'application_name' => '{application_name}',
            ],
        ]));

        $this->assertEquals('rocketeer.json', $this->localStorage->getFilename());
    }

    public function testCanNormalizeFilename()
    {
        $this->localStorage->setFilename('foo/Bar.json');

        $this->assertEquals('bar.json', $this->localStorage->getFilename());
    }

    public function testCanSwapContents()
    {
        $matcher = ['foo' => 'caca'];
        $this->localStorage->set($matcher);
        $contents = $this->localStorage->get();
        unset($contents['hash']);

        $this->assertEquals($matcher, $contents);
    }

    public function testCanGetValue()
    {
        $this->assertEquals('bar', $this->localStorage->get('foo'));
    }

    public function testCanSetValue()
    {
        $this->localStorage->set('foo', 'baz');

        $this->assertEquals('baz', $this->localStorage->get('foo'));
    }

    public function testCanDestroy()
    {
        $this->localStorage->destroy();

        $this->assertFalse($this->files->has($this->localStorage->getFilepath()));
    }

    public function testDoesntTryToDestroyTwice()
    {
        $this->localStorage->destroy();
        $this->localStorage->destroy();
    }

    public function testCanFallbackIfFileDoesntExist()
    {
        $this->localStorage->destroy();

        $this->assertEquals(null, $this->localStorage->get('foo'));
    }

    public function testUsesLocalFilesystemIfLocalMode()
    {
        $this->rocketeer->setLocal(true);
        $this->filesystems->mountFilesystem('remote', new Filesystem(new SftpAdapter([])));

        $storage = new ServerStorage($this->container);
        $this->assertInstanceOf(Local::class, $storage->getFilesystem()->getAdapter());
    }

    public function testAccessFilesClassDirectlyIfLocal()
    {
        $this->rocketeer->setLocal(true);

        $storage = new ServerStorage($this->container);
        $this->assertInstanceOf(Local::class, $storage->getFilesystem()->getAdapter());
    }
}
