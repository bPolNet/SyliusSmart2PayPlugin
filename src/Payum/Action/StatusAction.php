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

        switch ($status) {
            case Api::STATUS_NEW:
                $request->markNew();
                break;

            case Api::STATUS_SUSPENDED:
                $request->markSuspended();
                break;

            case Api::STATUS_PENDING:
                $request->markPending();
                break;

            case Api::STATUS_AUTHORIZED:
                $request->markAuthorized();
                break;

            case Api::STATUS_EXPIRED:
                $request->markExpired();
                break;

            case Api::STATUS_FAILED:
                $request->markFailed();
                break;

            case Api::STATUS_CANCELLED:
                $request->markCanceled();
                break;

            case Api::STATUS_CAPTURED: // no break here is intentional
            case Api::STATUS_SUCCESS:
                $request->markCaptured();
                break;

            case Api::STATUS_REFUNDED:
                $request->markRefunded();
                break;

            case Api::STATUS_PAYEDOUT:
                $request->markPayedout();
                break;

            case Api::STATUS_UNKNOWN:
            default:
                $request->markUnknown();
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getFirstModel() instanceof SyliusPaymentInterface
            ;
    }
}
