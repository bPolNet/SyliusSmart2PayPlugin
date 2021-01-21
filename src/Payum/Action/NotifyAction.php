<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Payum\Action;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;

final class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    /** @var Api */
    protected $api;

    /** @var PaymentRepositoryInterface */
    private $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;

        $this->apiClass = Api::class;
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (!$this->api->authorizeRequest($httpRequest)) {
            throw new HttpResponse('Authorization failed', 403);
        }

        $paymentId = $this->extractPaymentId($httpRequest);
        $payment = $this->paymentRepository->find($paymentId);

        if (!$payment instanceof SyliusPaymentInterface) {
            throw new HttpResponse('Payment not found', 404);
        }

        $request->setModel($payment);

        // notification statuses taken from
        // https://docs.smart2pay.com/category/payments-api/payment-notification/payment-notification-format/

        if ($this->notificationStatusSuccess($httpRequest)) {
            $this->updatePaymentDetailsWithStatus($payment, Api::STATUS_SUCCESS);
            throw new HttpResponse('OK', 204);
        }

        if ($this->notificationStatusOpen($httpRequest)) {
            $this->updatePaymentDetailsWithStatus($payment, Api::STATUS_PROCESSING);
            throw new HttpResponse('OK', 204);
        }

        if ($this->notificationStatusCaptured($httpRequest)) {
            $this->updatePaymentDetailsWithStatus($payment, Api::STATUS_PROCESSING);
            throw new HttpResponse('OK', 204);
        }

        if ($this->notificationStatusFailed($httpRequest)) {
            $this->updatePaymentDetailsWithStatus($payment, Api::STATUS_FAILED);
            throw new HttpResponse('OK', 204);
        }

        throw new HttpResponse('Could not handle notification', 400);

        // then we go to StatusAction
    }

    public function supports($request): bool
    {
        return $request instanceof Notify;
    }

    private function extractPaymentId(GetHttpRequest $httpRequest): string
    {
        $content = json_decode($httpRequest->content, true);
        return $content['Payment']['MerchantTransactionID'] ?? '';
    }

    private function updatePaymentDetailsWithStatus(SyliusPaymentInterface $payment, string $status): void
    {
        $details = $payment->getDetails();
        $details['status'] = $status;
        $payment->setDetails($details);
    }

    private function notificationStatusSuccess(GetHttpRequest $httpRequest): bool
    {
        $content = json_decode($httpRequest->content, true);
        $statusId = $content['Payment']['Status']['ID'] ?? null;

        return (int)$statusId === 2;
    }

    private function notificationStatusOpen(GetHttpRequest $httpRequest): bool
    {
        $content = json_decode($httpRequest->content, true);
        $statusId = $content['Payment']['Status']['ID'] ?? null;

        return (int)$statusId === 1;
    }

    private function notificationStatusCaptured(GetHttpRequest $httpRequest): bool
    {
        $content = json_decode($httpRequest->content, true);
        $statusId = $content['Payment']['Status']['ID'] ?? null;

        return (int)$statusId === 11;
    }

    private function notificationStatusFailed(GetHttpRequest $httpRequest): bool
    {
        $content = json_decode($httpRequest->content, true);
        $statusId = $content['Payment']['Status']['ID'] ?? null;

        return (int)$statusId === 4;
    }
}
