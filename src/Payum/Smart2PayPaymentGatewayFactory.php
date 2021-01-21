<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Payum;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class Smart2PayPaymentGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'smart2pay_payment',
            'payum.factory_title' => 'Smart2Pay Payment',
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'return_url' => '',
                'environment' => Api::ENVIRONMENT_TEST,
            ];

            $config->defaults($config['payum.default_options']);

            $config['payum.required_options'] = ['api_key', 'site_id', 'return_url', 'environment'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api($config['site_id'], $config['api_key'], $config['return_url'], $config['environment']);
            };
        }
    }
}
