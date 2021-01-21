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
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    /** @var Api */
    protected $api;

    public function __construct()
    {
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
            $apiParameters = $this->prepareApiParameters($payment, $request);
            $callParams = $this->prepareCallParameters();
            $finalizeParams = $this->prepareFinalizeParameters();

            // try to create payment in Smart2Pay
            $response = \S2P_SDK\S2P_SDK_Module::quick_call($apiParameters, $callParams, $finalizeParams);

            $noResponse = !is_array($response);
            if ($noResponse) {
                $error = 'Unknown error during create payment.';
                if (($errorArray = \S2P_SDK\S2P_SDK_Module::st_get_error()) && !empty($errorArray['display_error'])) {
                    $error = $errorArray['display_error'];
                }
                $payment->setDetails([
                    'status' => Api::STATUS_FAILED,
                    'error' => $error
                ]);

                return;
            }

            $createPaymentSuccessful = !empty($response['finalize_result']['should_redirect'])
                && !empty($response['finalize_result']['redirect_to']);

            if (!$createPaymentSuccessful) {
                $payment->setDetails([
                    'status' => Api::STATUS_FAILED,
                    'error' => 'Wrong response during create payment',
                    'response' => $response
                ]);

                return;
            }

            $payment->setDetails($response);

            // redirect to SmartToPay payment
            throw new HttpRedirect($response['finalize_result']['redirect_to']);


        } else {
            // return from SmartToPay payment site
            $details = $this->mapRequestToDetails($httpRequest);
            $payment->setDetails($details);

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
     * @param SyliusPaymentInterface $payment
     * @param Capture $request
     * @return array
     */
    protected function prepareApiParameters(SyliusPaymentInterface $payment, Capture $request): array
    {
        $apiParameters = [];

        // By default, API will check S2P_SDK_API_KEY, S2P_SDK_SITE_ID and S2P_SDK_ENVIRONMENT constats set in config.php
        // If you want to override these constants (per request) uncomment lines below and provide values to override
        // $apiParameters['api_key'] = $this->api->getConfig()['api_key'];
        // $apiParameters['site_id'] = $this->api->getConfig()['site_id'];
        // $apiParameters['environment'] = 'test'; // test or live

        // !!! SmartCards note !!!
        // If $apiParameters['method_params']['payment']['methodid'] is 6
        // (SmartCards method - \S2P_SDK\S2P_SDK_Module::is_smartcards_method( $method_id )), you should normally send
        // $apiParameters['method'] = 'cards';. However, since SDK v2.1.23 $apiParameters['method'] will be automatically
        // changed to 'cards' in case 'payments' is provided

        $apiParameters['method'] = 'payments';
        $apiParameters['func'] = 'payment_init';

        $apiParameters['get_variables'] = [];
        $apiParameters['method_params'] = [
            'payment' => [ // Mandatory
                'merchanttransactionid' => $payment->getId(),
                'amount' => $payment->getOrder()->getTotal(),
                'currency' => $payment->getOrder()->getCurrencyCode(),
                'returnurl' => $this->api->getReturnUrl($request),
                'methodid' => null,
                'siteid' => null,
                'description' => $payment->getOrder()->getNumber(),
                'customer' => [
                    'merchantcustomerid' => '',
                    'email' => $payment->getOrder()->getCustomer()->getEmail(),
                    'firstname' => $payment->getOrder()->getCustomer()->getFirstName(),
                    'lastname' => $payment->getOrder()->getCustomer()->getLastName(),
                    'phone' => '',
                    'company' => '',
                ],
                'billingaddress' => [
                    'country' => $payment->getOrder()->getBillingAddress()->getCountryCode(),
                    'city' => $payment->getOrder()->getBillingAddress()->getCity(),
                    'zipcode' => $payment->getOrder()->getBillingAddress()->getPostcode(),
                    'state' => $payment->getOrder()->getBillingAddress()->getProvinceName(),
                    'street' => $payment->getOrder()->getBillingAddress()->getStreet(),
                    'streetnumber' => '',
                    'housenumber' => '',
                    'houseextension' => '',
                ],
                'shippingaddress' => [
                    'country' => $payment->getOrder()->getShippingAddress()->getCountryCode(),
                    'city' => $payment->getOrder()->getShippingAddress()->getCity(),
                    'zipcode' => $payment->getOrder()->getShippingAddress()->getPostcode(),
                    'state' => $payment->getOrder()->getShippingAddress()->getProvinceName(),
                    'street' => $payment->getOrder()->getShippingAddress()->getStreet(),
                    'streetnumber' => '',
                    'housenumber' => '',
                    'houseextension' => '',
                ],
                'tokenlifetime' => 15,
            ],
        ];

        return $apiParameters;
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

    private function mapRequestToDetails(GetHttpRequest $httpRequest): array
    {
        $details = [
            'data' => $httpRequest->query['data'],
            'paymentId' => $httpRequest->query['MerchantTransactionID'],
            'statusId' => $httpRequest->query['StatusID'] ?? '',
            'statusName' => $httpRequest->query['StatusName'] ?? '',
        ];

        if ($this->redirectionStatusSuccessful($details)) {
            $status = Api::STATUS_SUCCESS;
        } else if ($this->redirectionStatusCancelled($details)) {
            $status = Api::STATUS_CANCELLED;
        } else if ($this->redirectionStatusFailed($details)) {
            $status = Api::STATUS_FAILED;
        } else if ($this->redirectionStatusProcessing($details)) {
            $status = Api::STATUS_PROCESSING;
        } else if ($this->redirectionStatusAuthorized($details)) {
            $status = Api::STATUS_AUTHORIZED;
        } else {
            $status = Api::STATUS_UNKNOWN;
        }

        $details['status'] = $status;

        return $details;
    }

    private function redirectionStatusSuccessful(array $details): bool
    {
        return $details['data'] === '2';
    }

    private function redirectionStatusCancelled(array $details): bool
    {
        return $details['data'] === '3';
    }

    private function redirectionStatusFailed(array $details): bool
    {
        return $details['data'] === '7'
            && !isset($details['StatusID'])
            && !isset($details['StatusName']);
    }

    private function redirectionStatusProcessing(array $details): bool
    {
        return $details['data'] === '7'
            && $details['StatusID'] === '7'
            && $details['StatusName'] === 'PendingOnProvider';
    }

    private function redirectionStatusAuthorized(array $details): bool
    {
        return $details['data'] === '9';
    }
}
