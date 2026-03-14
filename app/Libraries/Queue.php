<?php

namespace App\Libraries;

/**
 * Beanstalkd queue wrapper using raw socket protocol.
 *
 * No external dependencies — communicates with Beanstalkd via its
 * simple text protocol over a persistent TCP connection.
 *
 * @see https://raw.githubusercontent.com/beanstalkd/beanstalkd/master/doc/protocol.txt
 */
class Queue
{
    /** @var resource|false */
    private $socket;

    /**
     * Open a connection to Beanstalkd.
     *
     * Host is read from the BEANSTALK environment variable (falls back
     * to the Docker service name "beanstalkd"). Port is always 11300.
     */
    public function __construct()
    {
        $host = getenv('BEANSTALK') ?: 'beanstalkd';
        $port = 11300;

        $this->socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if ($this->socket) {
            stream_set_timeout($this->socket, 30);
        }
    }

    // ------------------------------------------------------------------
    //  Producer
    // ------------------------------------------------------------------

    /**
     * Push a job onto a tube.
     *
     * @param string $tube    Tube name (e.g. "payroll", "emails").
     * @param array  $payload Arbitrary data — will be JSON-encoded.
     * @param int    $delay   Seconds before the job becomes ready.
     * @param int    $pri     Priority (0 = most urgent, 2^32-1 = least).
     * @param int    $ttr     Time-to-run in seconds.
     *
     * @return int|false  The job id on success, false on failure.
     */
    public function push(string $tube, array $payload, int $delay = 0, int $pri = 1024, int $ttr = 120)
    {
        if (! $this->socket) {
            return false;
        }

        // Select the tube for producing.
        $resp = $this->sendCommand("use {$tube}");
        if (strpos($resp, 'USING') !== 0) {
            return false;
        }

        // Encode payload.
        $data  = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $bytes = strlen($data);

        // The "put" command is special: it is followed by the data block
        // on the *same* write, separated by \r\n.
        $cmd = "put {$pri} {$delay} {$ttr} {$bytes}\r\n{$data}";
        $resp = $this->sendCommand($cmd);

        // Expected: "INSERTED <id>\r\n"
        if (preg_match('/^INSERTED (\d+)/', $resp, $m)) {
            return (int) $m[1];
        }

        return false;
    }

    // ------------------------------------------------------------------
    //  Consumer
    // ------------------------------------------------------------------

    /**
     * Watch a tube and reserve a single job (blocking up to $timeout seconds).
     *
     * @param string $tube    Tube to watch.
     * @param int    $timeout Seconds to wait for a job (0 = return immediately).
     *
     * @return array|null  ['id' => int, 'payload' => array] or null.
     */
    public function reserve(string $tube, int $timeout = 5): ?array
    {
        if (! $this->socket) {
            return null;
        }

        // Watch the requested tube.
        $resp = $this->sendCommand("watch {$tube}");
        if (strpos($resp, 'WATCHING') !== 0) {
            return null;
        }

        // Ignore the "default" tube so we only receive from our tube.
        // (Harmless if "default" is not currently watched.)
        $this->sendCommand('ignore default');

        // Reserve with timeout — the server blocks until a job is ready
        // or the timeout expires.
        $resp = $this->sendCommand("reserve-with-timeout {$timeout}");

        // Expected on success: "RESERVED <id> <bytes>\r\n<data>\r\n"
        // On timeout:           "TIMED_OUT\r\n"
        if (preg_match('/^RESERVED (\d+) (\d+)/', $resp, $m)) {
            $jobId = (int) $m[1];
            $bytes = (int) $m[2];

            // Read the data block (exactly $bytes bytes + trailing \r\n).
            $data = $this->readData($bytes);

            return [
                'id'      => $jobId,
                'payload' => json_decode($data, true) ?: [],
            ];
        }

        return null;
    }

    /**
     * Delete a job (acknowledge successful processing).
     */
    public function delete(int $jobId): bool
    {
        if (! $this->socket) {
            return false;
        }

        $resp = $this->sendCommand("delete {$jobId}");

        return trim($resp) === 'DELETED';
    }

    /**
     * Bury a job so it is held aside for later inspection.
     */
    public function bury(int $jobId, int $pri = 1024): bool
    {
        if (! $this->socket) {
            return false;
        }

        $resp = $this->sendCommand("bury {$jobId} {$pri}");

        return trim($resp) === 'BURIED';
    }

    /**
     * Kick at most $bound buried/delayed jobs back into the ready queue.
     */
    public function kick(int $bound = 1): int
    {
        if (! $this->socket) {
            return 0;
        }

        $resp = $this->sendCommand("kick {$bound}");

        if (preg_match('/^KICKED (\d+)/', $resp, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    /**
     * Return server statistics as an associative array (stats command).
     */
    public function stats(): array
    {
        if (! $this->socket) {
            return [];
        }

        $resp = $this->sendCommand('stats');

        if (preg_match('/^OK (\d+)/', $resp, $m)) {
            $yaml = $this->readData((int) $m[1]);
            return $this->parseYaml($yaml);
        }

        return [];
    }

    /**
     * Check whether the connection to Beanstalkd is alive.
     */
    public function isConnected(): bool
    {
        return is_resource($this->socket);
    }

    // ------------------------------------------------------------------
    //  Protocol helpers
    // ------------------------------------------------------------------

    /**
     * Send a command and read the one-line response.
     */
    private function sendCommand(string $cmd): string
    {
        fwrite($this->socket, $cmd . "\r\n");

        $line = fgets($this->socket);

        return $line !== false ? $line : '';
    }

    /**
     * Read exactly $bytes of data from the socket, plus the trailing \r\n.
     */
    private function readData(int $bytes): string
    {
        $data      = '';
        $remaining = $bytes;

        while ($remaining > 0) {
            $chunk = fread($this->socket, $remaining);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data      .= $chunk;
            $remaining -= strlen($chunk);
        }

        // Consume the trailing \r\n after the data block.
        fread($this->socket, 2);

        return $data;
    }

    /**
     * Minimal YAML parser for Beanstalkd stats output (key: value lines).
     */
    private function parseYaml(string $yaml): array
    {
        $result = [];
        foreach (explode("\n", $yaml) as $line) {
            $line = trim($line);
            if ($line === '' || $line === '---') {
                continue;
            }
            if (strpos($line, ': ') !== false) {
                [$key, $value] = explode(': ', $line, 2);
                $result[trim($key, '- ')] = trim($value);
            }
        }
        return $result;
    }

    // ------------------------------------------------------------------
    //  Lifecycle
    // ------------------------------------------------------------------

    /**
     * Cleanly close the socket on destruction.
     */
    public function __destruct()
    {
        if (is_resource($this->socket)) {
            $this->sendCommand('quit');
            fclose($this->socket);
        }
    }
}
