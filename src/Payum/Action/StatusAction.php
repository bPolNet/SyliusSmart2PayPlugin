<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Payum\Action;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class StatusAction implements ActionInterface
{
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var GetStatusInterface $request */

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();
        $details = $payment->getDetails();
        $status = $details['status'] ?? Api::STATUS_UNKNOWN;

        if ($status === Api::STATUS_SUCCESS) {
            $request->markCaptured();

            return;
        }

        if ($status === Api::STATUS_CANCELLED) {
            $request->markCanceled();

            return;
        }

        if ($status === Api::STATUS_FAILED) {
            $request->markFailed();

            return;
        }

        if ($status === Api::STATUS_PROCESSING) {
            $request->markPending();

            return;
        }

        if ($status === Api::STATUS_AUTHORIZED) {
            $request->markAuthorized();

            return;
        }

        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getFirstModel() instanceof SyliusPaymentInterface
            ;
    }
}
