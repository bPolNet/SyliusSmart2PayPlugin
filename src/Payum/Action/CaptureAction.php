<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Payum\Action;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use BPolNet\SyliusSmart2PayPlugin\Payum\Mapper\ApiParameters;
use BPolNet\SyliusSmart2PayPlugin\Payum\Mapper\PaymentStatus;
use BPolNet\SyliusSmart2PayPlugin\Traits\UpdatesPaymentDetails;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use UpdatesPaymentDetails;

    /** @var Api */
    protected $api;

    /** @var ApiParameters */
    private $apiParameters;

    /** @var PaymentStatus */
    private $paymentStatus;

    public function __construct(ApiParameters $apiParameters, PaymentStatus $paymentStatus)
    {
        $this->apiParameters = $apiParameters;
        $this->paymentStatus = $paymentStatus;

        $this->apiClass = Api::class;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Capture $request */

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        $isReturnFromSmart2Pay = isset($httpRequest->query['data']) && isset($httpRequest->query['MerchantTransactionID']);
        if (!$isReturnFromSmart2Pay) {
            $apiParameters = $this->apiParameters->prepare($payment, $this->api->getReturnUrl($request));
            $callParams = $this->prepareCallParameters();
            $finalizeParams = $this->prepareFinalizeParameters();

            // try to create payment in Smart2Pay
            $response = \S2P_SDK\S2P_SDK_Module::quick_call($apiParameters, $callParams, $finalizeParams);

            $noResponse = !is_array($response);
            if ($noResponse) {
                $this->updatePaymentDetails($payment, Api::STATUS_FAILED, Api::SOURCE_REQUEST, [
                    'error' => \S2P_SDK\S2P_SDK_Module::st_get_error()
                ]);
                return;
            }

            $paymentCreated = !empty($response['finalize_result']['should_redirect'])
                && !empty($response['finalize_result']['redirect_to']);

            if (!$paymentCreated) {
                $this->updatePaymentDetails($payment, Api::STATUS_FAILED, Api::SOURCE_REQUEST, [
                    'error' => 'redirect_to not set on create payment response',
                    'response' => $response
                ]);

                return;
            }

            $s2pPaymentId = $response['call_result']['payment']['id'] ?? '';
            $this->updatePaymentDetails($payment, Api::STATUS_PENDING, Api::SOURCE_REQUEST, [
                'response' => $response,
                's2p_payment_id' => $s2pPaymentId,
            ]);

            // redirect to SmartToPay payment
            throw new HttpRedirect($response['finalize_result']['redirect_to']);


        } else {
            // return from SmartToPay payment site
            $statusId = (int)($httpRequest->query['data'] ?? 0);
            $status = $this->paymentStatus->mapFromStatusId($statusId);
            $this->updatePaymentDetails($payment, $status, Api::SOURCE_RETURN, [
                'status_id' => $statusId,
                'request' => $httpRequest->query,
            ]);

            // then we go to StatusAction
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface;
    }

    /**
     * @return array
     */
    protected function prepareCallParameters(): array
    {
        $callParams = [];
        $callParams['curl_params'] = [
            // In case you use proxy
            // 'proxy_server' => '8.8.8.8:888',
            // In case you need proxy authentication
            // 'proxy_auth' => 'user:pass',
            // For full access to cURL handler
            // 'curl_init_callback' => 'api_curl_extra_init',
            // Use constant function so in case constant is not set, it will be empty and cURL call function would choose a default value
            'connection_ssl_version' => constant('CURL_SSLVERSION_TLSv1_2'),
        ];
        return $callParams;
    }

    /**
     * @return array
     */
    protected function prepareFinalizeParameters(): array
    {
        $finalizeParams = [];
        $finalizeParams['redirect_now'] = false;
        return $finalizeParams;
    }
}
