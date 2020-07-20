<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Decorator;

use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;

final class AggregateIdTypeEventDecorator implements MessageDecorator
{
    private MessageAlias $messageAlias;

    public function __construct(MessageAlias $messageAlias)
    {
        $this->messageAlias = $messageAlias;
    }

    public function decorate(Message $message): Message
    {
        if (!$this->isMessageCanBeDecorated($message)) {
            return $message;
        }

        $aggregateId = $message->header(MessageHeader::AGGREGATE_ID);

        if (!$aggregateId instanceof AggregateId) {
            throw new RuntimeException('Aggregate id must be an instance of ' . AggregateId::class);
        }

        return $message->withHeader(
            MessageHeader::AGGREGATE_ID_TYPE, $this->messageAlias->instanceToType($aggregateId)
        );
    }

    private function isMessageCanBeDecorated(Message $message): bool
    {
        if (!$message->isMessaging() || $message->event()->messageType() !== Messaging::EVENT) {
            return false;
        }

        return null === $aggregateIdType = $message->header(MessageHeader::AGGREGATE_ID_TYPE);
    }
}
