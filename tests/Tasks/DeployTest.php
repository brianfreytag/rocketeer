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

namespace Rocketeer\Tasks;

use Rocketeer\Strategies\Deploy\CopyStrategy;
use Rocketeer\TestCases\RocketeerTestCase;

class DeployTest extends RocketeerTestCase
{
    public function testCanDeployToServer()
    {
        $this->swapConfig([
            'scm.repository' => 'https://github.com/'.$this->repository,
            'scm.username' => '',
            'scm.password' => '',
        ]);

        $matcher = [
            'git clone "{repository}" "{server}/releases/{release}" --branch="master" --depth="1"',
            [
                'cd {server}/releases/{release}',
                'git submodule update --init --recursive',
            ],
            [
                'cd {server}/releases/{release}',
                '{phpunit} --stop-on-failure',
            ],
            [
                'cd {server}/releases/{release}',
                'chmod -R 755 {server}/releases/{release}/tests',
                'chmod -R g+s {server}/releases/{release}/tests',
                'chown -R www-data:www-data {server}/releases/{release}/tests',
            ],
            'mv {server}/current {server}/releases/{release}',
            [
                'ln -s {server}/releases/{release} {server}/current-temp',
                'mv -Tf {server}/current-temp {server}/current',
            ],
        ];

        $this->assertTaskHistory('Deploy', $matcher, [
            'tests' => true,
            'seed' => true,
            'migrate' => true,
        ]);
    }

    public function testStepsRunnerDoesntCancelWithPermissionsAndShared()
    {
        $this->swapConfig([
            'remote.shared' => [],
            'remote.permissions.files' => [],
        ]);

        $matcher = [
            'git clone "{repository}" "{server}/releases/{release}" --branch="master" --depth="1"',
            [
                'cd {server}/releases/{release}',
                'git submodule update --init --recursive',
            ],
            [
                'cd {server}/releases/{release}',
                '{phpunit} --stop-on-failure',
            ],
            'mv {server}/current {server}/releases/{release}',
            [
                'ln -s {server}/releases/{release} {server}/current-temp',
                'mv -Tf {server}/current-temp {server}/current',
            ],
        ];

        $this->assertTaskHistory('Deploy', $matcher, [
            'tests' => true,
            'seed' => true,
            'migrate' => true,
        ]);
    }

    public function testCanDisableGitOptions()
    {
        $this->swapConfig([
            'scm.shallow' => false,
            'scm.submodules' => false,
            'scm.repository' => 'https://github.com/'.$this->repository,
            'scm.username' => '',
            'scm.password' => '',
        ]);

        $matcher = [
            'git clone "{repository}" "{server}/releases/{release}" --branch="master"',
            [
                'cd {server}/releases/{release}',
                '{phpunit} --stop-on-failure',
            ],
            [
                'cd {server}/releases/{release}',
                'chmod -R 755 {server}/releases/{release}/tests',
                'chmod -R g+s {server}/releases/{release}/tests',
                'chown -R www-data:www-data {server}/releases/{release}/tests',
            ],
            'mv {server}/current {server}/releases/{release}',
            [
                'ln -s {server}/releases/{release} {server}/current-temp',
                'mv -Tf {server}/current-temp {server}/current',
            ],
        ];

        $this->assertTaskHistory('Deploy', $matcher, [
            'tests' => true,
            'seed' => true,
            'migrate' => true,
        ]);
    }

    public function testCanUseCopyStrategy()
    {
        $this->expectRepositoryConfig('https://github.com/'.$this->repository);
        $this->container->add('rocketeer.strategies.deploy', new CopyStrategy($this->container));

        $this->mockState([
            '10000000000000' => true,
        ]);

        $matcher = [
            'cp -a {server}/releases/10000000000000 {server}/releases/{release}',
            [
                'cd {server}/releases/{release}',
                'git reset --hard',
                'git pull',
            ],
            [
                'cd {server}/releases/{release}',
                'chmod -R 755 {server}/releases/{release}/tests',
                'chmod -R g+s {server}/releases/{release}/tests',
                'chown -R www-data:www-data {server}/releases/{release}/tests',
            ],
            'mv {server}/current {server}/releases/{release}',
            [
                'ln -s {server}/releases/{release} {server}/current-temp',
                'mv -Tf {server}/current-temp {server}/current',
            ],
        ];

        $this->assertTaskHistory('Deploy', $matcher, [
            'tests' => false,
            'seed' => false,
            'migrate' => false,
        ]);
    }

    public function testCanRunDeployWithSeed()
    {
        $matcher = [
            'git clone "{repository}" "{server}/releases/{release}" --branch="master" --depth="1"',
            [
                'cd {server}/releases/{release}',
                'git submodule update --init --recursive',
            ],
            [
                'cd {server}/releases/{release}',
                'chmod -R 755 {server}/releases/{release}/tests',
                'chmod -R g+s {server}/releases/{release}/tests',
                'chown -R www-data:www-data {server}/releases/{release}/tests',
            ],
            'mv {server}/current {server}/releases/{release}',
            [
                'ln -s {server}/releases/{release} {server}/current-temp',
                'mv -Tf {server}/current-temp {server}/current',
            ],
        ];

        $this->assertTaskHistory('Deploy', $matcher, [
            'tests' => false,
            'seed' => true,
            'migrate' => false,
        ]);
    }

    public function testNoDbRoleNoMigrationsNorSeedsAreRun()
    {
        $this->swapConnections([
            'production' => [
                'servers' => [
                    [
                        'db_role' => false,
                    ],
                ],
            ],
        ]);

        $this->swapConfig([
            'scm.repository' => 'https://github.com/'.$this->repository,
            'scm.username' => '',
            'scm.password' => '',
        ]);

        $matcher = [
            'git clone "{repository}" "{server}/releases/{release}" --branch="master" --depth="1"',
            [
                'cd {server}/releases/{release}',
                'git submodule update --init --recursive',
            ],
            [
                'cd {server}/releases/{release}',
                '{phpunit} --stop-on-failure',
            ],
            [
                'cd {server}/releases/{release}',
                'chmod -R 755 {server}/releases/{release}/tests',
                'chmod -R g+s {server}/releases/{release}/tests',
                'chown -R www-data:www-data {server}/releases/{release}/tests',
            ],
            'mv {server}/current {server}/releases/{release}',
            [
                'ln -s {server}/releases/{release} {server}/current-temp',
                'mv -Tf {server}/current-temp {server}/current',
            ],
        ];

        $this->assertTaskHistory('Deploy', $matcher, [
            'tests' => true,
            'seed' => true,
            'migrate' => true,
        ]);
    }

    public function testDbRoleMigrationsAndSeedsAreRun()
    {
        $this->swapConnections([
            'production' => [
                'servers' => [
                    [
                        'db_role' => true,
                    ],
                ],
            ],
        ]);

        $this->swapConfig([
            'scm.repository' => 'https://github.com/'.$this->repository,
            'scm.username' => '',
            'scm.password' => '',
        ]);

        $matcher = [
            'git clone "{repository}" "{server}/releases/{release}" --branch="master" --depth="1"',
            [
                'cd {server}/releases/{release}',
                'git submodule update --init --recursive',
            ],
            [
                'cd {server}/releases/{release}',
                '{phpunit} --stop-on-failure',
            ],
            [
                'cd {server}/releases/{release}',
                'chmod -R 755 {server}/releases/{release}/tests',
                'chmod -R g+s {server}/releases/{release}/tests',
                'chown -R www-data:www-data {server}/releases/{release}/tests',
            ],
            'mv {server}/current {server}/releases/{release}',
            [
                'ln -s {server}/releases/{release} {server}/current-temp',
                'mv -Tf {server}/current-temp {server}/current',
            ],
        ];

        $this->assertTaskHistory('Deploy', $matcher, [
            'tests' => true,
            'seed' => true,
            'migrate' => true,
        ]);
    }
}
