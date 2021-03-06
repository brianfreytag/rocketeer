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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\TreeBuilder\NodeBuilder;

class PathsDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    protected $name = 'paths';

    /**
     * @var string
     */
    protected $description = "Here you can manually set paths to some commands Rocketeer might try to use.\nIf you leave those empty it will try to find them manually or assume they're in the root folder";

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $node = $builder->root($this->name, 'array', new NodeBuilder());
        $node = $node->info($this->description);

        return $node
            ->defaultValue([
                'app' => getcwd(),
                'php' => null,
                'composer' => null,
            ])
            ->useAttributeAsKey('name')
            ->prototype('scalar')->end();
    }
}
