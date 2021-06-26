<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Payum\Mapper;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use BPolNet\SyliusSmart2PayPlugin\Payum\PaymentMethod;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

class ApiParameters
{
    public function prepare(SyliusPaymentInterface $payment, string $returnUrl, string $paymentMethod): array
    {
        $apiParameters = [];

        // By default, API will check S2P_SDK_API_KEY, S2P_SDK_SITE_ID and S2P_SDK_ENVIRONMENT constants set in config.php
        // If you want to override these constants (per request) uncomment lines below and provide values to override
        // $apiParameters['api_key'] = $this->api->getConfig()['api_key'];
        // $apiParameters['site_id'] = $this->api->getConfig()['site_id'];
        // $apiParameters['environment'] = 'test'; // test or live

        // !!! SmartCards note !!!
        // If $apiParameters['method_params']['payment']['methodid'] is 6
        // (SmartCards method - \S2P_SDK\S2P_SDK_Module::is_smartcards_method( $method_id )), you should normally send
        // $apiParameters['method'] = 'cards';. However, since SDK v2.1.23 $apiParameters['method'] will be automatically
        // changed to 'cards' in case 'payments' is provided

        $apiParameters['method'] = $paymentMethod;
        $apiParameters['func'] = 'payment_init';

        $apiParameters['get_variables'] = [];

        $order = $payment->getOrder();

        $customer = $order->getCustomer();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        $apiParameters['method_params'] = [
            'payment' => [ // Mandatory
                'merchanttransactionid' => sprintf('%s-%d', $order->getNumber(), $payment->getId()),
                'amount' => $payment->getAmount(),
                'currency' => $payment->getCurrencyCode(),
                'returnurl' => $returnUrl,
                'methodid' => null,
                'siteid' => null,
                'description' => $order->getNumber(),
//                'ExcludeMethodIDs' => [
//                    // this could be moved to configuration page (see Smart2PayGatewayConfigurationType.php)
//                    // it does not work in S2P sandbox. Need to contact with S2P
//                    PaymentMethod::TRUSTLY,
//                ],
                'customer' => [
                    'merchantcustomerid' => $customer ? $customer->getId() : '',
                    'email' => $customer ? $customer->getEmail() : '',
                    'firstname' => $customer ? $customer->getFirstName() : '',
                    'lastname' => $customer ? $customer->getLastName() : '',
                    'phone' => $customer ? $customer->getPhoneNumber() : '',
                    'company' => '',
                ],
                'billingaddress' => [
                    'country' => $billingAddress ? $billingAddress->getCountryCode() : '',
                    'city' => $billingAddress ? $billingAddress->getCity() : '',
                    'zipcode' => $billingAddress ? $billingAddress->getPostcode() : '',
                    'state' => $billingAddress ? $billingAddress->getProvinceName() : '',
                    'street' => $billingAddress ? $billingAddress->getStreet() : '',
                    'streetnumber' => '',
                    'housenumber' => '',
                    'houseextension' => '',
                ],
                'shippingaddress' => [
                    'country' => $shippingAddress ? $shippingAddress->getCountryCode() : '',
                    'city' => $shippingAddress ? $shippingAddress->getCity() : '',
                    'zipcode' => $shippingAddress ? $shippingAddress->getPostcode() : '',
                    'state' => $shippingAddress ? $shippingAddress->getProvinceName() : '',
                    'street' => $shippingAddress ? $shippingAddress->getStreet() : '',
                    'streetnumber' => '',
                    'housenumber' => '',
                    'houseextension' => '',
                ],
                'tokenlifetime' => 15,
            ],
        ];

        if ($paymentMethod === Api::METHOD_CARDS) {
            $apiParameters['method_params']['payment']['capture'] = true;
        }

        return $apiParameters;
    }
}
