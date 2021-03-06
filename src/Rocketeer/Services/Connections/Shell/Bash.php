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

namespace Rocketeer\Services\Connections\Shell;

use League\Container\ContainerAwareInterface;
use Rocketeer\Services\Modules\ModulableInterface;
use Rocketeer\Services\Modules\ModulableTrait;
use Rocketeer\Traits\ContainerAwareTrait;
use Rocketeer\Traits\Properties\HasHistoryTrait;

/**
 * An helper to execute low-level commands on the remote server.
 *
 * @mixin Modules\Binaries
 * @mixin Modules\Core
 * @mixin Modules\Filesystem
 * @mixin Modules\Flow
 *
 * @method string binary($binary)
 * @method string checkStatus($error, $output = null, $success = null)
 * @method string copy($origin, $destination)
 * @method string copyFromPreviousRelease($folder)
 * @method string createFolder($folder = null, $recursive = false)
 * @method string fileExists($file)
 * @method string getConnection()
 * @method string getExtraOutput()
 * @method string getTimestamp()
 * @method string isSetup()
 * @method string isSymlink($folder)
 * @method string listContents($directory)
 * @method string move($origin, $destination)
 * @method string processCommands($commands)
 * @method string put($file, $contents)
 * @method string rawWhich($location)
 * @method string read($file)
 * @method string removeFolder($folders = null)
 * @method string run($commands, $silent = false, $array = false)
 * @method string runForApplication($tasks)
 * @method string runForCurrentRelease($tasks)
 * @method string runInFolder($folder = null, $tasks = [])
 * @method string runLast($commands)
 * @method string runLocally($commands)
 * @method string runRaw($commands, $array = false, $trim = false)
 * @method string runSilently($commands, $array = false)
 * @method string setPermissions($folder)
 * @method string share($file)
 * @method string shellCommand($command)
 * @method string status()
 * @method string symlink($folder, $symlink)
 * @method string syncSharedFolders()
 * @method string tail($file, $continuous = true)
 * @method string updateSymlink($release = null)
 * @method string upload($file, $destination = null)
 * @method string usesStages()
 * @method string which($binary, $fallback = null, $prompt = true)
 *
 * @author Maxime Fabre <ehtnam6@gmail.com>
 */
class Bash implements ModulableInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use HasHistoryTrait;
    use ModulableTrait;

    /**
     * Whether to run the commands locally
     * or on the server.
     *
     * @var bool
     */
    protected $local = false;

    /**
     * @param bool $local
     */
    public function setLocal($local)
    {
        $this->local = $local;
    }

    /**
     * @return bool
     */
    public function isLocal()
    {
        return $this->local;
    }

    /**
     * Run a series of commands in local.
     *
     * @param callable $callback
     *
     * @return bool
     */
    public function onLocal(callable $callback)
    {
        $current = $this->rocketeer->isLocal();

        $this->rocketeer->setLocal(true);
        $results = $callback($this);
        $this->rocketeer->setLocal($current);

        return $results;
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// RUNNERS ///////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Get the implementation behind a strategy.
     *
     * @param string      $strategy
     * @param string|null $concrete
     * @param array       $options
     *
     * @return \Rocketeer\Strategies\AbstractStrategy
     */
    public function getStrategy($strategy, $concrete = null, $options = [])
    {
        // Try to build the strategy
        $strategy = $this->builder->buildStrategy($strategy, $concrete);
        if (!$strategy || !$strategy->isExecutable()) {
            return;
        }

        // Configure strategy
        if ($options) {
            $options = array_replace_recursive((array) $options, $strategy->getOptions());
            $strategy->setOptions($options);
        }

        return $this->explainer->displayBelow(function () use ($strategy, $options) {
            return $strategy->displayStatus();
        });
    }

    /**
     * Execute another task by name.
     *
     * @param string $tasks
     *
     * @return string|false
     */
    public function executeTask($tasks)
    {
        $results = $this->explainer->displayBelow(function () use ($tasks) {
            return $this->builder->buildTask($tasks)->fire();
        });

        return $results;
    }

    /**
     * @param string $hook
     * @param array  $arguments
     *
     * @return string|array|null
     */
    protected function getHookedTasks($hook, array $arguments)
    {
        $tasks = $this->config->getContextually('strategies.'.$hook);
        if (!is_callable($tasks)) {
            return;
        }

        // Cancel if no tasks to execute
        $tasks = (array) $tasks(...$arguments);
        if (empty($tasks)) {
            return;
        }

        // Run commands
        return $tasks;
    }
}
