<?php
namespace Genkgo\Api\Integration;

use Genkgo\Api\Connection;
use Genkgo\Api\Exception\ResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ErrorResponseTest extends \PHPUnit_Framework_TestCase {

    public function testClientError () {
        $httpRequest = $this->getMockBuilder(Client::class)->setMethods(['post'])->getMock();
        $httpResponse = $this->getMock(ResponseInterface::class);
        $httpException = new ServerException('', $this->getMock(RequestInterface::class), $httpResponse);

        $httpResponse->expects($this->any())->method('getStatusCode')->willReturn(404);
        $httpResponse->method('getBody')->willReturn('error message');

        $httpRequest
            ->expects($this->once())
            ->method('post')
            ->with('https://www.url.com/', [
                'form_params' => [
                    'token' => 'token',
                    'part' => 'unknown',
                    'command' => 'unknown'
                ]])
            ->willThrowException($httpException)
        ;

        $this->setExpectedException(ResponseException::class, 'Request failed with command unknown, status code 404 and message error message');
        $connection = new Connection($httpRequest, 'https://www.url.com/', 'token');
        $connection->command('unknown', 'unknown');
    }

    public function testResponseError () {
        $httpRequest = $this->getMockBuilder(Client::class)->setMethods(['post'])->getMock();
        $httpResponse = $this->getMock(MessageInterface::class);

        $httpRequest
            ->expects($this->once())
            ->method('post')
            ->with('https://www.url.com/', [
                'form_params' => [
                    'token' => 'token',
                    'part' => 'unknown',
                    'command' => 'unknown'
                ]])
            ->willReturn($httpResponse)
        ;

        $httpResponse
            ->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn([])
        ;

        $this->setExpectedException(ResponseException::class, 'Response did not contains a Content-Type header');
        $connection = new Connection($httpRequest, 'https://www.url.com/', 'token');
        $connection->command('unknown', 'unknown');
    }

    public function testUnknownContentType () {
        $httpRequest = $this->getMockBuilder(Client::class)->setMethods(['post'])->getMock();
        $httpResponse = $this->getMock(MessageInterface::class);

        $httpRequest
            ->expects($this->once())
            ->method('post')
            ->with('https://www.url.com/', [
                'form_params' => [
                    'token' => 'token',
                    'part' => 'unknown',
                    'command' => 'unknown'
                ]])
            ->willReturn($httpResponse)
        ;

        $httpResponse
            ->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn(['application/unknown'])
        ;

        $this->setExpectedException(ResponseException::class, 'Unknown response type application/unknown');

        $connection = new Connection($httpRequest, 'https://www.url.com/', 'token');
        $response = $connection->command('unknown', 'unknown');
        $response->getBody();
    }

    public function testMalformedContentType () {
        $httpRequest = $this->getMockBuilder(Client::class)->setMethods(['post'])->getMock();
        $httpResponse = $this->getMock(MessageInterface::class);

        $httpRequest
            ->expects($this->once())
            ->method('post')
            ->with('https://www.url.com/', [
                'form_params' => [
                    'token' => 'token',
                    'part' => 'unknown',
                    'command' => 'unknown'
                ]])
            ->willReturn($httpResponse)
        ;

        $httpResponse
            ->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn(['application/unknown; UTF-8'])
        ;

        $this->setExpectedException(ResponseException::class, 'Wrong content type, malformed charset: UTF-8');

        $connection = new Connection($httpRequest, 'https://www.url.com/', 'token');
        $response = $connection->command('unknown', 'unknown');
        $response->getBody();
    }

}