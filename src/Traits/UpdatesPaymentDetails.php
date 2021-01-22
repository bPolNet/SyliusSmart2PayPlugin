<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Traits;

use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

trait UpdatesPaymentDetails
{
    protected function updatePaymentDetails(SyliusPaymentInterface $payment, string $status, string $statusSource, array $additionalDetails = []): void
    {
        $details = $payment->getDetails();

        $details['status'] = $status;
        $details['status_source'] = $statusSource;

        $details = array_merge($details, $additionalDetails);

        $payment->setDetails($details);
    }
}
