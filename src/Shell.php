<?php

namespace Crocos\Plugin\DeployPlugin;

class Shell
{
    const CONSOLE_USLEEP = 100000; // 0.1 sec
    const STREAM_TIMEOUT = 1;

    protected $spec = [
        [ 'pipe', 'r' ],
        [ 'pipe', 'w' ],
        [ 'pipe', 'w' ],
    ];

    protected $bufferSize = 40960;
    protected $cwd;

    protected $process;
    protected $in;
    protected $out;
    protected $err;

    protected $inputCallbacks;
    protected $outputCallbacks;
    protected $errorCallbacks;

    protected function open($command)
    {
        $this->process = proc_open($command, $this->spec, $pipes, $this->cwd);
        $this->in = $pipes[0];
        $this->out = $pipes[1];
        $this->err = $pipes[2];

        stream_set_blocking($this->in, 0);
        stream_set_blocking($this->out, 0);
        stream_set_blocking($this->err, 0);
    }

    protected function close()
    {
        fclose($this->in);
        fclose($this->out);
        fclose($this->err);
        proc_close($this->process);
    }

    protected function read()
    {
        return fread($this->out, $this->bufferSize);
    }

    protected function readError()
    {
        return fread($this->err, $this->bufferSize);
    }

    protected function write($data)
    {
        fwrite($this->in, $data);
    }

    protected function getExitCode()
    {
        $status = proc_get_status($this->process);

        if (false === $status['running']) {
            return $status['exitcode'];
        }

        return -1;
    }

    public function setCwd($cwd)
    {
        $this->cwd = $cwd;
    }

    public function addInputCallback(callable $callback)
    {
        if (count($this->inputCallbacks) > 1) {
            throw new \LogicException('cant add multiple input callback: use clearCallbaks()');
        }
        $this->inputCallbacks[] = $callback;
    }

    public function addOutputCallback(callable $callback)
    {
        $this->outputCallbacks[] = $callback;
    }

    public function addErrorCallback(callable $callback)
    {
        $this->errorCallbacks[] = $callback;
    }

    public function setInputCallback(callable $callback)
    {
        $this->inputCallbacks = [ $callback ];
    }

    public function setOutputCallback(callable $callback)
    {
        $this->outputCallbacks = [ $callback ];
    }

    public function setErrorCallback(callable $callback)
    {
        $this->errorCallbacks = [ $callback ];
    }

    public function setDefaultInputCallback()
    {
        $this->setInputCallback(function () { return fgets(STDIN); });
    }

    public function setDefaultOutputCallback()
    {
        $this->setOutputCallback(function ($m) { fwrite(STDOUT, $m); });
    }

    public function setDefaultErrorCallback()
    {
        $this->setErrorCallback(function ($m) { fwrite(STDERR, $m); });
    }

    public function setDefaultCallback()
    {
        // set default callback: stdio.
        $this->setDefaultInputCallback();
        $this->setDefaultOutputCallback();
        $this->setDefaultErrorCallback();
    }

    public function clearCallbacks()
    {
        $this->inputCallbacks = [];
        $this->outputCallbacks = [];
        $this->errorCallbacks = [];
    }

    public function run($command, callable $temporaryOutput = null)
    {
        $this->open($command);

        $stdout = $stderr = '';
        while (feof($this->out) === false || feof($this->err) === false) {
            $read = [ $this->out, $this->err ];
            $write = [ $this->in ];
            $except = null;
            $return = stream_select(
                $read,
                $write,
                $except,
                static::STREAM_TIMEOUT
            );

            if ($return === false) {
                throw new \RuntimeException('stream select error.');
            }

            foreach ($read as $socket) {
                if ($socket === $this->out) {
                    if (empty($stdout = $this->read())) continue;
                    if (count($this->outputCallbacks)) array_walk($this->outputCallbacks, function ($callback) use ($stdout) {
                        $callback($stdout);
                    });
                    if (! is_null($temporaryOutput)) {
                        $temporaryOutput($stdout);
                    }
                }
                if ($socket === $this->err) {
                    if (empty($stderr = $this->readError())) continue;
                    if (count($this->errorCallbacks)) array_walk($this->errorCallbacks, function ($callback) use ($stderr) {
                        $callback($stderr);
                    });
                    if (! is_null($temporaryOutput)) {
                        $temporaryOutput($stderr);
                    }
                }
            }
            foreach ($write as $socket) {
                if ($socket === $this->in) {
                    if (count($this->inputCallbacks)) array_walk($this->inputCallbacks, function ($callback) {
                        $this->write($callback());
                    });
                }
            }

            usleep(static::CONSOLE_USLEEP);
        }
        $exitCode = $this->getExitCode();
        $this->close();

        return $exitCode;
    }
}
