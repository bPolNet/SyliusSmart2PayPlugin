<?php

declare(strict_types=1);


namespace BPolNet\SyliusSmart2PayPlugin\Payum\Status;

use Sylius\Bundle\PayumBundle\Request\GetStatus;

class SyliusGetStatus extends GetStatus
{
    public const STATE_AWAITING_CONFIRMATION = 'Awaiting for payment confirmation';

    public function markAwaitingConfirmation()
    {
        $this->status = self::STATE_AWAITING_CONFIRMATION;
    }
}
