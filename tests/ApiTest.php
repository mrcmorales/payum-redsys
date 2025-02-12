<?php

namespace MrcMorales\Payum\Redsys\Tests;

use MrcMorales\Payum\Redsys\Api;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ApiTest extends TestCase
{

    #[Test]
    public function constructSetOptionsCorrectly()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);
        $reflectionClass = new ReflectionClass($api);
        $reflectionProperty = $reflectionClass->getProperty('options');
        $optionsValue = $reflectionProperty->getValue($api);
        $this->assertEquals($options, $optionsValue);
    }

    #[Test]
    public function throwIfMerchantCodeOptionNotSetInConstructor()
    {
        $this->expectException(InvalidArgumentException::class);
        new Api(array());
    }

    #[Test]
    public function throwIfMerchantCodeOptionIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        new Api(array(
            'merchant_code' => ''
        ));
    }

    #[Test]
    public function throwIfTerminalOptionNotSetInConstructor()
    {
        $this->expectException(InvalidArgumentException::class);
        new Api(array(
            'merchant_code' => 'a_merchant_code'
        ));
    }

    #[Test]
    public function throwIfTerminalOptionIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        new Api(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => ''
        ));
    }

    #[Test]
    public function throwIfSecretKeyOptionNotSetInConstructor()
    {
        $this->expectException(InvalidArgumentException::class);

        new Api(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal'
        ));
    }

    #[Test]
    public function throwIfSecretKeyOptionIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);

        new Api(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => ''
        ));
    }

    #[Test]
    public function throwIfSandboxOptionIsNotBoolean()
    {
        $this->expectException(InvalidArgumentException::class);

        new Api(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => '*****',
            'sandbox' => 'string'
        ));
    }

    #[Test]
    public function shouldReturnSandboxUrlIfInSandboxMode()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals('https://sis-t.redsys.es:25443/sis/realizarPago', $api->getApiEndpoint());
    }

    #[Test]
    public function shouldReturnProductionEnvIfNotInSandboxMode()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => false,
        );

        $api = new Api($options);

        $this->assertEquals('https://sis.redsys.es/sis/realizarPago', $api->getApiEndpoint() );
    }


    #[Test]
    public function throwIsCurrencyIsNotSupported()
    {
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $currencyCode = $api->getISO4127( 'XXX' );
    }

    #[Test]
    public function ISO4127Test()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals('978', $api->getISO4127('EUR'));
        $this->assertEquals('840', $api->getISO4127('USD'));
    }


    #[DataProvider('orderNumberProvider')]
    #[Test]
    public function shouldThrowIfOrderNumberHasNotValidFormat($orderNumber)
    {
        $this->expectException(LogicException::class);
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $api->ensureCorrectOrderNumber($orderNumber);
    }

    public static function orderNumberProvider(): array
    {
        return array(
            array('a'),
            array('abcd'),
            array('111a111'),
            array('1234abcd#efg'),
            array('1234ñ')
        );
    }

    #[DataProvider('longOrderNumberProvider')]
    #[Test]
    public function shouldThrowIfOrderNumberHasMoreThan12Characters($orderNumber)
    {
        $this->expectException(LogicException::class);
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $api->ensureCorrectOrderNumber($orderNumber);
    }

    public static function longOrderNumberProvider(): array
    {
        return array(
            array('1234567890123'),
            array('1234abcdefghi')
        );
    }

    public static function shortOrderNumberProvider(): array
    {
        return array(
            array('1', '0001'),
            array('12', '0012'),
            array('123', '0123'),
            array('1234', '1234'),
            array('1234a', '1234a')
        );
    }

    #[DataProvider('shortOrderNumberProvider')]
    #[Test]
    public function showBuildAOrderNumberWithAtLeast4Characters($orderNumber, $correctedOrderNumber)
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals($correctedOrderNumber, $api->ensureCorrectOrderNumber($orderNumber));
    }

    public static function validOrderNumberProvider(): array
    {
        return array(
            array('1234'),
            array('123412341234'),
            array('1234aA'),
            array('1234AABBCCDD'),
            array('1234abcdefgh')
        );
    }


    #[DataProvider('validOrderNumberProvider')]
    #[Test]
    public function shouldReturnOrderNumberPassedIfValid($orderNumber)
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals($orderNumber, $api->ensureCorrectOrderNumber($orderNumber));
    }

    #[Test]
    public function showReturnOptionsFromGetters()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals('a_merchant_code', $api->getMerchantCode());
        $this->assertEquals('a_terminal', $api->getMerchantTerminalCode());
    }
}
