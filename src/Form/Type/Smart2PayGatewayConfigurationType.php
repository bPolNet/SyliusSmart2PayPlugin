<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Form\Type;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class Smart2PayGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('api_key', TextType::class, [
                'label' => 'bpolnet.smart2pay_plugin.api_key',
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'bpolnet.smart2pay_plugin.gateway_configuration.api_key.not_blank',
                            'groups' => ['sylius'],
                        ]
                    ),
                ],
            ])
            ->add('site_id', TextType::class, [
                'label' => 'bpolnet.smart2pay_plugin.site_id',
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'bpolnet.smart2pay_plugin.site_id.not_blank',
                            'groups' => ['sylius'],
                        ]
                    ),
                ],
            ])
            ->add('environment', ChoiceType::class, [
                'choices' => [
                    'bpolnet.smart2pay_plugin.production' => Api::ENVIRONMENT_LIVE,
                    'bpolnet.smart2pay_plugin.sandbox' => Api::ENVIRONMENT_TEST,
                ],
                'label' => 'bpolnet.smart2pay_plugin.environment',
            ])
            ->add('payment_method', ChoiceType::class, [
                'choices' => [
                    'bpolnet.smart2pay_plugin.credit_cards' => Api::METHOD_CARDS,
                    'bpolnet.smart2pay_plugin.payments' => Api::METHOD_PAYMENTS
                ],
                'label' => 'bpolnet.smart2pay_plugin.payment_method',
            ])
            ->add('return_url', TextType::class, [
                'label' => 'bpolnet.smart2pay_plugin.return_url',
                'required' => false,
                'help' => 'bpolnet.smart2pay_plugin.return_url_help',
            ]);
    }
}

