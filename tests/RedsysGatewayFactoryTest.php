<?php

namespace MrcMorales\Payum\Redsys\Tests;

use MrcMorales\Payum\Redsys\RedsysGatewayFactory;

use Payum\Core\GatewayFactory;
use Payum\Core\GatewayFactoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RedsysGatewayFactoryTest extends TestCase
{

    #[Test]
    public function ShouldImplementGatewayFactoryInterface()
    {
        $rc = new \ReflectionClass(RedsysGatewayFactory::class);
        $this->assertTrue($rc->implementsInterface(GatewayFactoryInterface::class));
    }


    #[Test]
    public function CouldBeConstructedWithoutAnyArguments()
    {
       $factory =  new RedsysGatewayFactory();
       $this->assertInstanceOf(RedsysGatewayFactory::class, $factory);
    }


    #[Test]
    public function ShouldCreateCoreGatewayFactoryIfNotPassed()
    {
        $factory = new RedsysGatewayFactory();
        $this->assertInstanceOf(GatewayFactory::class, $factory);
    }

    #[Test]
    public function shouldUseCoreGatewayFactoryPassedAsSecondArgument()
    {
        $coreGatewayFactory = $this->createMock(GatewayFactoryInterface::class);
        $factory = new RedsysGatewayFactory([], $coreGatewayFactory);
        $reflection = new \ReflectionProperty(RedsysGatewayFactory::class, 'coreGatewayFactory');
        $actual = $reflection->getValue($factory);
        $this->assertSame($coreGatewayFactory, $actual);
    }

    #[Test]
    public function shouldAllowCreateGatewayConfig()
    {
        $factory = new RedsysGatewayFactory();
        $config = $factory->createConfig();
        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }

    #[Test]
    public function shouldAddDefaultConfigPassedInConstructorWhileCreatingGatewayConfig()
    {
        $factory = new RedsysGatewayFactory(array(
            'foo' => 'fooVal',
            'bar' => 'barVal',
        ));
        $config = $factory->createConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('foo', $config);
        $this->assertEquals('fooVal', $config['foo']);
        $this->assertArrayHasKey('bar', $config);
        $this->assertEquals('barVal', $config['bar']);
    }

    #[Test]
    public function shouldConfigContainDefaultOptions()
    {
        $factory = new RedsysGatewayFactory();
        $config = $factory->createConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('payum.default_options', $config);
        $this->assertEquals(
            array('merchant_code' => '', 'terminal' => '', 'secret_key' => '', 'sandbox' => true),
            $config['payum.default_options']
        );
    }

    #[Test]
    public function shouldConfigContainFactoryNameAndTitle()
    {
        $factory = new RedsysGatewayFactory();
        $config = $factory->createConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('payum.factory_name', $config);
        $this->assertEquals('redsys', $config['payum.factory_name']);
        $this->assertArrayHasKey('payum.factory_title', $config);
        $this->assertEquals('Redsys', $config['payum.factory_title']);
    }

    #[Test]
    public function shouldThrowIfRequiredOptionsNotPassed()
    {
        $this->expectExceptionMessage("The merchant_code, terminal, secret_key fields are required.");
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $factory = new RedsysGatewayFactory();
        $factory->create();
    }
}

