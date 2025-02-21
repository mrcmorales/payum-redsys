<?php

namespace MrcMorales\Payum\Redsys\Action;

use MrcMorales\Payum\Redsys\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;

class StatusAction implements ActionInterface
{
    /**
     * @param GetStatusInterface $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (null == $model['Ds_Response']) {
            $request->markNew();

            return;
        }

        if ($model['Ds_AuthorisationCode'] && null === $model['Ds_Response']) {
            $request->markPending();

            return;
        }

        if (in_array($model['Ds_Response'],
            [Api::DS_RESPONSE_CANCELED, Api::DS_RESPONSE_USER_CANCELED])) {
            $request->markCanceled();

            return;
        }

        if (0 <= $model['Ds_Response'] && 99 >= $model['Ds_Response']) {
            $request->markCaptured();

            return;
        }

        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface
            && $request->getModel() instanceof \ArrayAccess
        ;
    }
}
