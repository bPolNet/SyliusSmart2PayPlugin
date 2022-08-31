<?php

declare(strict_types=1);


namespace BPolNet\SyliusSmart2PayPlugin\Payum\Status;

use Payum\Core\Request\GetStatusInterface;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Payment\Model\PaymentInterface;

class SyliusGetStatus extends GetStatus
{

    public function markAwaitingConfirmation()
    {
        $this->status = PaymentInterface::STATE_PROCESSING;
    }
}
