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

namespace Rocketeer\Services\Config\Definition;

use Illuminate\Support\Arr;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\TreeBuilder\NodeBuilder;

abstract class AbstractDefinition implements ConfigurationInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var array
     */
    protected $values;

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $node = $builder->root($this->name, 'array', new NodeBuilder());
        $node = $node->info($this->description);

        $node = $node->children();
        $node = $this->getChildren($node);
        $node = $node->end();

        return $node;
    }

    /**
     * @param NodeBuilder $node
     *
     * @return NodeBuilder
     */
    protected function getChildren(NodeBuilder $node)
    {
        return $node;
    }

    ////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////// PRESETS ////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * @param array $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    protected function value($key, $default = null)
    {
        return Arr::get($this->values, $key, $default);
    }
}
