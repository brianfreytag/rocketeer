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

namespace Rocketeer\Services\Connections\Credentials;

use Closure;
use Rocketeer\Services\Connections\Credentials\Keys\RepositoryKey;
use Rocketeer\Traits\ContainerAwareTrait;

/**
 * Collects the needed credentials from the user.
 */
class CredentialsGatherer
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $connections = [];

    /**
     * @return array
     */
    public function getCredentials()
    {
        $credentials = $this->getRepositoryCredentials();
        $connections = $this->getConnectionsCredentials();
        foreach ($connections as $connection) {
            $credentials = array_merge($credentials, $connection);
        }

        return $credentials;
    }

    /**
     * Get the Repository's credentials.
     *
     * @return array
     */
    public function getRepositoryCredentials()
    {
        return $this->askQuestions('scm', [
            'repository' => 'Where is your code located? <comment>(eg. git@github.com:rocketeers/website.git)</comment>',
            'username' => 'What is the username for it?',
            'password' => 'And the password?',
        ]);
    }

    /**
     * Get the credentials of all connections.
     *
     * @return array
     */
    public function getConnectionsCredentials()
    {
        $connectionName = null;
        if ($this->connections) {
            $this->command->writeln('Here are the current connections defined:');
            $this->command->table(['Name', 'Server', 'Username', 'Password'], $this->connections);
            if ($this->command->confirm('Do you want to add a connection to this?', false)) {
                $connectionName = $this->command->ask('What do you want to name it?');
            }
        } else {
            $connectionName = $this->command->ask('No connections have been set, let\'s create one, what do you want to name it?', 'production');
        }

        // If the user does not want to add any more connection
        // then we can quit
        if (!$connectionName) {
            return $this->connections;
        }

        $this->getConnectionCredentials($connectionName);

        return $this->getConnectionsCredentials();
    }

    /**
     * Get the credentials of a connection.
     *
     * @param string $connectionName
     */
    public function getConnectionCredentials($connectionName)
    {
        $usesPrivateKey = $this->command->confirm('Do you use an SSH key to connect to it?');
        $questions = $usesPrivateKey ? [
            'key' => ['Where can I find your key?', $this->paths->getDefaultKeyPath()],
            'keyphrase' => 'If it needs a passphrase enter it',
            'host' => 'Where is your server located? <comment>(eg. foobar.com)</comment>',
            'username' => 'What is the username for it?',
            'root' => ['Where do you want your application deployed?', '/home/www/'],
        ] : [
            'host' => 'Where is your server located?',
            'username' => 'What is the username for it?',
            'password' => 'And password?',
            'root' => ['Where do you want your application deployed?', '/home/www/'],
        ];

        $this->connections[$connectionName] = $this->askQuestions($connectionName, $questions);
    }

    ////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////// HELPERS ////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $for
     * @param array  $questions
     *
     * @return array
     */
    protected function askQuestions($for, array $questions)
    {
        $credentials = [];
        $config = [];
        foreach ($questions as $credential => $question) {
            $answer = $this->askQuestion($for, $credential, $question);

            // Store credential
            $constant = $this->getCredentialConstant($for, $credential);
            $credentials[$constant] = $answer;
            $config[$credential] = '%%'.$constant.'%%';

            // If the repository uses SSH, do not ask for username/password
            $repositoryKey = new RepositoryKey(['endpoint' => $answer]);
            if ($for === 'scm' && $credential === 'repository' && !$repositoryKey->needsCredentials()) {
                break;
            }
        }

        // Set in current configuration
        $configKey = $for === 'scm' ? 'scm' : 'connections.'.$for;
        $this->config->set($configKey, $config);

        return $credentials;
    }

    /**
     * @param string $for
     * @param string $credential
     * @param string $question
     *
     * @return string
     */
    protected function askQuestion($for, $credential, $question)
    {
        $isPassword = in_array($credential, ['keyphrase', 'password'], true);

        // Prepend who the question is for to name to question
        $question = (array) $question;
        $question[0] = '<fg=magenta>['.$for.']</fg=magenta> '.$question[0];
        if ($isPassword) {
            $question[] = $this->getCredentialsValidator();
        }

        // Get the credential, either through options or prompt
        if (($for !== 'scm' || $credential === 'repository') && $option = $this->command->option($credential)) {
            return $option;
        }

        return $isPassword ? $this->command->askHidden(...$question) : $this->command->ask(...$question);
    }

    /**
     * @param string $prefix
     * @param string $credential
     *
     * @return string
     */
    protected function getCredentialConstant($prefix, $credential)
    {
        return strtoupper($prefix.'_'.$credential);
    }

    /**
     * @return Closure
     */
    protected function getCredentialsValidator()
    {
        return function () {
            return true;
        };
    }
}
