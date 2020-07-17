<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Plexikon\Chronicle\Stream\StreamName;

final class StreamNotFound extends RuntimeException
{
    public static function withStreamName(StreamName $streamName): self
    {
        return new self("Stream name {$streamName} not found");
    }
}
