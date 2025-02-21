<?php

namespace MrcMorales\Payum\Redsys;

use MrcMorales\Payum\Redsys\Action\AuthorizeAction;
use MrcMorales\Payum\Redsys\Action\CaptureAction;
use MrcMorales\Payum\Redsys\Action\ConvertPaymentAction;
use MrcMorales\Payum\Redsys\Action\NotifyAction;
use MrcMorales\Payum\Redsys\Action\RefundAction;
use MrcMorales\Payum\Redsys\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class RedsysGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'redsys',
            'payum.factory_title' => 'Redsys',
            'payum.action.capture' => new CaptureAction(),

            // TODO
            //            'payum.action.authorize' => new AuthorizeAction(),
            //            'payum.action.refund' => new RefundAction(),

            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        $config['payum.default_options'] = [
            'merchant_code' => '',
            'terminal' => '',
            'secret_key' => '',
            'sandbox' => true,
        ];
        $config->defaults($config['payum.default_options']);
        $config['payum.required_options'] = ['merchant_code', 'terminal', 'secret_key'];

        $config['payum.api'] = function (ArrayObject $config) {
            $config->validateNotEmpty($config['payum.required_options']);
            $config = [
                'merchant_code' => $config['merchant_code'],
                'terminal' => $config['terminal'],
                'secret_key' => $config['secret_key'],
                'sandbox' => $config['sandbox'],
            ];

            return new Api($config);
        };
    }
}
