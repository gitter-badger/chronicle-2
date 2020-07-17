<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Throwable;

class ReportEvent extends ReportMessage
{
    public function dispatch($event): void
    {
        $context = $this->tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($event);

        try {
            $this->dispatchMessage($context);
        } catch (Throwable $exception) {
            $context->withRaisedException($exception);
        } finally {
            $this->finalizeDispatching($context);
        }
    }
}
