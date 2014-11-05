<?php
namespace Crocos\Navy\DeployPlugin\Release;

class CommandContext
{
    protected $commands = [];
    protected $env = [];

    public function setEnv(array $env)
    {
        $this->env = $env;
    }

    public function getEnv($key = null)
    {
        if ($key !== null) {
            return isset($this->env[$key]) ? $this->env[$key] : null;
        }

        return $this->env;
    }

    public function set($name, CommandInterface $command)
    {
        list($name, $argNames) = static::parseName($name);

        $command->setContext($this);

        $this->commands[$name] = [$command, $argNames];
    }

    public function get($name)
    {
        list($name, $argValues) = static::parseName($name);

        if (!isset($this->commands[$name])) {
            throw new \InvalidArgumentException(sprintf('No command found "%s"', $name));
        }

        list($command, $argNames) = $this->commands[$name];

        if (count($argNames) !== count($argValues)) {
            throw new \InvalidArgumentException(sprintf('Invalid command args for "%s" ("%s" given)', $name, json_encode($argValues)));
        }

        $args = array_combine($argNames, $argValues);

        // as immutable
        $command = clone $command;
        $command->setArgs($args);

        return $command;
    }

    public function run($name)
    {
        $command = $this->get($name);

        return $command->run();
    }

    public static function parseName($name)
    {
        $args = [];
        if ((strpos($name, '(') !== false) && preg_match('/^([\w-]+)\(([\w\s,-]+)\)$/', $name, $matches)) {
            $name = $matches[1];
            $args = array_map('trim', explode(',', $matches[2]));
        }

        return [$name, $args];
    }
}
