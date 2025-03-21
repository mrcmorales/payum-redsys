<?php

namespace MrcMorales\Payum\Redsys\Tests\Action;

use MrcMorales\Payum\Redsys\Action\RefundAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Request\Refund;
use Payum\Core\Tests\GenericActionTest;
use PHPUnit\Framework\Attributes\Test;

class RefundActionTest extends GenericActionTest
{
    protected $requestClass = Refund::class;
    protected $actionClass = RefundAction::class;

    #[Test]
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass($this->actionClass);
        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    #[Test]
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass($this->actionClass);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }
}
