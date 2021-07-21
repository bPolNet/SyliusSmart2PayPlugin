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
use Psr\Log\LoggerInterface;
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

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        PaymentStatus $paymentStatusNotification,
        LoggerInterface $logger
    )
    {
        $this->paymentRepository = $paymentRepository;
        $this->paymentStatus = $paymentStatusNotification;
        $this->logger = $logger;

        $this->apiClass = Api::class;
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (!$this->api->authorizeRequest($httpRequest)) {
            $this->logger->error('Smart2Pay: notification authorization failed');
            throw new HttpResponse(null, 204);
        }

        $requestContent = $this->getDecodedRequestContent($httpRequest);
        $paymentId = $this->extractPaymentId($requestContent);
        $payment = $this->paymentRepository->find($paymentId);
        if (!$payment instanceof SyliusPaymentInterface) {
            $this->logger->error(sprintf('Smart2Pay: payment "%s" not found', $paymentId));
            throw new HttpResponse(null, 204);
        }

        $request->setModel($payment);

        $statusId = $this->extractStatusId($requestContent);
        $status = $this->paymentStatus->mapFromStatusId($statusId);

        $this->updatePaymentDetails($payment, $status, Api::SOURCE_NOTIFICATION, [
            'status_id' => $statusId,
            'request' => json_decode($httpRequest->content, true),
        ]);

        if (!$this->hasValidAmountAndCurrency($payment, $requestContent)) {
            $this->logger->error('Smart2Pay: invalid payment amount or currency');
            throw new HttpResponse(null, 204);
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

        return explode('-', $merchantTransactionId)[1] ?? $merchantTransactionId;
    }

    private function extractStatusId(array $requestContent): int
    {
        return (int)($requestContent['Payment']['Status']['ID'] ?? 0);
    }
}
