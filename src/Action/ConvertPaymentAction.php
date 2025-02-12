<?php
namespace MrcMorales\Payum\Redsys\Action;

use MrcMorales\Payum\Redsys\Action\Api\BaseApiAwareAction;
use MrcMorales\Payum\Redsys\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentAction extends BaseApiAwareAction implements ActionInterface
{
    use GatewayAwareTrait;

    /** @var Api */
    protected $api;

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $details->defaults(array(
            'Ds_Merchant_Amount' => $payment->getTotalAmount(),
            'Ds_Merchant_Order' => $this->api->ensureCorrectOrderNumber($payment->getNumber()),
            'Ds_Merchant_MerchantCode' => $this->api->getMerchantCode(),
            'Ds_Merchant_Currency' => $this->api->getISO4127($payment->getCurrencyCode()),
            'Ds_Merchant_Terminal' => $this->api->getMerchantTerminalCode(),
        ));

        $request->setResult((array)$details);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}
