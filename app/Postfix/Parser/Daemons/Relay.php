<?php

declare(strict_types=1);

namespace App\Postfix\Parser\Daemons;

class Relay extends Daemon
{
    protected function getRegex(): string
    {
        return '/^(?<queue_id>[0-9A-Za-z]{14,16}|[0-9A-F]{10,11}): ?to=<?(?<to>[^>,]*)>?, ?relay=(?<relay>[^,]*), ?delay=(?<delay>[^,]+), delays=(?<delays>[^,]+), dsn=(?<dsn>[^,]+), status=(?<status>.*?) \((?<response>.*)\)$/';
    }
}
