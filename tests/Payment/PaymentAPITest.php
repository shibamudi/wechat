<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use EasyWeChat\Core\Http;
use EasyWeChat\Payment\API;
use EasyWeChat\Payment\Merchant;
use EasyWeChat\Payment\Order;
use EasyWeChat\Support\XML;

class PaymentAPITest extends PHPUnit_Framework_TestCase
{
    /**
     * Build API instance.
     *
     * @return API
     */
    public function getAPI()
    {
        $http = Mockery::mock(Http::class);

        $http->shouldReceive('post')->andReturnUsing(function ($api, $params) {
            $params = XML::parse($params);

            return XML::build(compact('api', 'params'));
        });

        $merchant = new Merchant([
                'fee_type' => 'CNY',
                'merchant_id' => 'testMerchantId',
                'app_id' => 'wxTestAppId',
                'device_info' => 'testDeviceInfo',
                'key' => 'testKey',
            ]);

        $api = Mockery::mock('EasyWeChat\Payment\API[getHttp]', [$merchant]);
        $api->shouldReceive('getHttp')->andReturn($http);

        return $api;
    }

    /**
     * Test prepare().
     */
    public function testPrepare()
    {
        $api = $this->getAPI();
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('all')->andReturn(['foo' => 'bar']);

        $response = $api->prepare($order);

        $this->assertEquals(API::API_PREPARE_ORDER, $response['api']);
        $this->assertEquals('wxTestAppId', $response['params']['appid']);
        $this->assertEquals('testMerchantId', $response['params']['mch_id']);
        $this->assertEquals('bar', $response['params']['foo']);
    }

    /**
     * Test query().
     */
    public function testQuery()
    {
        $api = $this->getAPI();
        $response = $api->query('testTradeNoFoo');

        $this->assertEquals(API::API_QUERY, $response['api']);
        $this->assertEquals('testTradeNoFoo', $response['params']['out_trade_no']);

        $response = $api->query('testTradeNoBar', API::TRANSCATION_ID);

        $this->assertEquals(API::API_QUERY, $response['api']);
        $this->assertEquals('testTradeNoBar', $response['params']['transcation_id']);

        $response = $api->queryByTranscationId('testTranscationId');
        $this->assertEquals(API::API_QUERY, $response['api']);
        $this->assertEquals('testTranscationId', $response['params']['transcation_id']);
    }

    /**
     * Test close().
     */
    public function testClose()
    {
        $api = $this->getAPI();

        $response = $api->close('testTradeNo');
        $this->assertEquals(API::API_CLOSE, $response['api']);
        $this->assertEquals('testTradeNo', $response['params']['out_trade_no']);
    }

    /**
     * Test reverse().
     */
    public function testReverse()
    {
        $api = $this->getAPI();

        $response = $api->reverse('testTradeNo');
        $this->assertEquals(API::API_REVERSE, $response['api']);
        $this->assertEquals('testTradeNo', $response['params']['out_trade_no']);

        $response = $api->reverse('testTranscationId', API::TRANSCATION_ID);
        $this->assertEquals(API::API_REVERSE, $response['api']);
        $this->assertEquals('testTranscationId', $response['params']['transcation_id']);
    }

    /**
     * Test refund.
     */
    public function testRefund()
    {
        $api = $this->getAPI();

        $response = $api->refund('testTradeNo', 100);
        $this->assertEquals(API::API_REFUND, $response['api']);
        $this->assertEquals(100, $response['params']['total_fee']);
        $this->assertEquals(100, $response['params']['refund_fee']);
        $this->assertEquals('CNY', $response['params']['refund_fee_type']);
        $this->assertEquals('testMerchantId', $response['params']['op_user_id']);
        $this->assertEquals('testTradeNo', $response['params']['out_trade_no']);

        $response = $api->refund('testTradeNo', 100, 50);
        $this->assertEquals(100, $response['params']['total_fee']);
        $this->assertEquals(50, $response['params']['refund_fee']);

        $response = $api->refund('testTradeNo', 100, 50, 'testRefundNo');
        $this->assertEquals(100, $response['params']['total_fee']);
        $this->assertEquals(50, $response['params']['refund_fee']);
    }

    /**
     * Test queryRefund().
     */
    public function testQueryRefund()
    {
        $api = $this->getAPI();

        $response = $api->queryRefund('testTradeNo');
        $this->assertEquals(API::API_QUERY_REFUND, $response['api']);
        $this->assertEquals('testTradeNo', $response['params']['out_trade_no']);

        $response = $api->queryRefund('testTranscationId', API::TRANSCATION_ID);
        $this->assertEquals(API::API_QUERY_REFUND, $response['api']);
        $this->assertEquals('testTranscationId', $response['params']['transcation_id']);
    }

    /**
     * Test downloadBill().
     */
    public function testDownloadBill()
    {
        $api = $this->getAPI();

        $response = $api->downloadBill('20150901');
        $this->assertEquals(API::API_DOWNLOAD_BILL, $response['api']);
        $this->assertEquals('20150901', $response['params']['bill_date']);
        $this->assertEquals(API::BILL_TYPE_ALL, $response['params']['bill_type']);

        $response = $api->downloadBill('20150901', API::BILL_TYPE_SUCCESS);
        $this->assertEquals(API::API_DOWNLOAD_BILL, $response['api']);
        $this->assertEquals('20150901', $response['params']['bill_date']);
        $this->assertEquals(API::BILL_TYPE_SUCCESS, $response['params']['bill_type']);
    }

    /**
     * Test urlShorten().
     */
    public function testUrlShorten()
    {
        $api = $this->getAPI();

        $response = $api->urlShorten('http://easywechat.org');

        $this->assertEquals(API::API_URL_SHORTEN, $response['api']);
        $this->assertEquals('http://easywechat.org', $response['params']['long_url']);
    }

    /**
     * Test authCodeToOpenId().
     */
    public function testAuthCodeToOpenId()
    {
        $api = $this->getAPI();

        $response = $api->authCodeToOpenId('authcode');

        $this->assertEquals(API::API_AUTH_CODE_TO_OPENID, $response['api']);
        $this->assertEquals('authcode', $response['params']['auth_code']);
    }

    /**
     * Test setMerchant() and getMerchant.
     */
    public function testMerchantGetterAndSetter()
    {
        $api = $this->getAPI();
        $merchant = Mockery::mock(Merchant::class);
        $api->setMerchant($merchant);

        $this->assertEquals($merchant, $api->getMerchant());
    }
}
