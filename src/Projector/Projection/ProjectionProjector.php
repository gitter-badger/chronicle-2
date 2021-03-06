<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Projection;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Projector\Concerns\HasPersistentProjector;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Reporter\DomainEvent;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\PersistentProjector;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectionProjector as BaseProjectionProjector;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Projector\StreamCached;

final class ProjectionProjector implements BaseProjectionProjector
{
    use HasPersistentProjector;

    protected ?string $streamName;
    protected ProjectorContext $projectorContext;
    protected Chronicler $chronicler;
    private StreamCached $streamCached;

    public function __construct(ProjectorContext $projectorContext,
                                ProjectorLock $projectorLock,
                                Chronicler $chronicler,
                                MessageAlias $messageAlias,
                                string $streamName)
    {
        $this->projectorContext = $projectorContext;
        $this->projectorLock = $projectorLock;
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
        $this->streamName = $streamName;
        $this->streamCached = new StreamCached($projectorContext->options->persistBlockSize());
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->streamName);

        if (!$this->projectorContext->isStreamCreated && !$this->chronicler->hasStream($streamName)) {
            $this->chronicler->persistFirstCommit(new Stream($streamName));

            $this->projectorContext->isStreamCreated = true;
        }

        $this->linkTo($this->streamName, $event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $streamName = new StreamName($streamName);

        $stream = new Stream($streamName, [new Message($event, $event->headers())]);

        if ($this->streamCached->has($streamName)) {
            $append = true;
        } else {
            $this->streamCached->toNextPosition($streamName);
            $append = $this->chronicler->hasStream($streamName);
        }

        $append
            ? $this->chronicler->persist($stream)
            : $this->chronicler->persistFirstCommit($stream);
    }

    protected function createEventHandlerContext(PersistentProjector $projector, ?string $streamName): object
    {
        return new class($projector, $streamName) {
            private ProjectionProjector $projector;
            private ?string $streamName;

            public function __construct(ProjectionProjector $projector, ?string &$streamName)
            {
                $this->projector = $projector;
                $this->streamName = &$streamName;
            }

            public function stop(): void
            {
                $this->projector->stop();
            }

            public function linkTo(string $streamName, DomainEvent $event): void
            {
                $this->projector->linkTo($streamName, $event);
            }

            public function emit(DomainEvent $event): void
            {
                $this->projector->emit($event);
            }

            public function streamName(): ?string
            {
                return $this->streamName;
            }
        };
    }
}
