<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscriber;

use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;

final class ChainMessageDecoratorSubscriber implements MessageSubscriber
{
    private MessageDecorator $messageDecorator;

    public function __construct(MessageDecorator $messageDecorator)
    {
        $this->messageDecorator = $messageDecorator;
    }

    public function attachToTracker(Tracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            $currentMessage = $context->getMessage();

            $context->withMessage(
                $this->messageDecorator->decorate($currentMessage)
            );
        }, 90000);
    }
}
