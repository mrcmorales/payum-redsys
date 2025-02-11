<?php
namespace MrcMorales\Payum\Redsys\Action;

use MrcMorales\Payum\Redsys\Action\Api\BaseApiAwareAction;
use MrcMorales\Payum\Redsys\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;

class NotifyAction extends BaseApiAwareAction implements ActionInterface
{
    use GatewayAwareTrait;

    /** @var Api */
    protected $api;

    /**
     * {@inheritDoc}
     *
     * @param Notify $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (null === $httpRequest->request['Ds_Signature']) {
            throw new HttpResponse('The notification is invalid', 400);
        }

        if (null === $httpRequest->request['Ds_MerchantParameters']) {
            throw new HttpResponse('The notification is invalid', 400);
        }

        if (false == $this->api->validateNotificationSignature($httpRequest->request)) {
            throw new HttpResponse('The notification is invalid', 400);
        }


        $details->replace(
            ArrayObject::ensureArrayObject(
                json_decode(base64_decode(strtr($httpRequest->request['Ds_MerchantParameters'], '-_', '+/')))
            )->toUnsafeArray() +
            $httpRequest->request
        );

        throw new HttpResponse('', 200);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
