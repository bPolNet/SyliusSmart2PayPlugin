<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Payum\Action;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use BPolNet\SyliusSmart2PayPlugin\Payum\Mapper\PaymentStatus;
use BPolNet\SyliusSmart2PayPlugin\Traits\UpdatesPaymentDetails;
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
    use UpdatesPaymentDetails;

    /** @var Api */
    protected $api;

    /** @var PaymentRepositoryInterface */
    private $paymentRepository;

    /** @var PaymentStatus */
    private $paymentStatus;

    public function __construct(PaymentRepositoryInterface $paymentRepository, PaymentStatus $paymentStatusNotification)
    {
        $this->paymentRepository = $paymentRepository;
        $this->paymentStatus = $paymentStatusNotification;

        $this->apiClass = Api::class;
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (!$this->api->authorizeRequest($httpRequest)) {
            throw new HttpResponse('Authorization failed', 403);
        }

        $requestContent = $this->getDecodedRequestContent($httpRequest);
        $paymentId = $this->extractPaymentId($requestContent);
        $payment = $this->paymentRepository->find($paymentId);
        if (!$payment instanceof SyliusPaymentInterface) {
            throw new HttpResponse('Payment not found', 404);
        }

        $request->setModel($payment);

        $statusId = $this->extractStatusId($requestContent);
        $status = $this->paymentStatus->mapFromStatusId($statusId);

        $this->updatePaymentDetails($payment, $status, Api::SOURCE_NOTIFICATION, [
            'status_id' => $statusId,
            'request' => json_decode($httpRequest->content, true),
        ]);

        if (!$this->hasValidAmountAndCurrency($payment, $requestContent)) {
            throw new HttpResponse('Invalid payment amount or currency', 400);
        }

        // then we go to StatusAction
    }

    public function supports($request): bool
    {
        return $request instanceof Notify;
    }

    private function getDecodedRequestContent(GetHttpRequest $httpRequest): array
    {
        return json_decode($httpRequest->content, true);
    }

    private function hasValidAmountAndCurrency(SyliusPaymentInterface $payment, array $requestContent):bool
    {
        return $payment->getCurrencyCode() === $this->extractCurrency($requestContent) &&
            $payment->getAmount() === $this->extractAmount($requestContent);
    }

    private function extractCurrency(array $requestContent): string
    {
        return $requestContent['Payment']['Currency'] ?? '';
    }

    private function extractAmount(array $requestContent): int
    {
        return (int)($requestContent['Payment']['Amount'] ?? 0);
    }

    private function extractPaymentId(array $requestContent): string
    {
        $merchantTransactionId = $requestContent['Payment']['MerchantTransactionID'] ?? '';

        return explode('-', $merchantTransactionId)[1] ?? '';
    }

    private function extractStatusId(array $requestContent): int
    {
        return (int)($requestContent['Payment']['Status']['ID'] ?? 0);
    }
}
