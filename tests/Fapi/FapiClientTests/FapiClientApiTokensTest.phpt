<?php
declare(strict_types = 1);

/**
 * Test: Fapi\FapiClient\FapiClient creating, getting, updating and deleting API tokens.
 *
 * @testCase Fapi\FapiClientTests\FapiClientApiTokensTest
 */

namespace Fapi\FapiClientTests;

use Fapi\FapiClient\AuthorizationException;
use Fapi\FapiClient\FapiClient;
use Fapi\FapiClient\NotFoundException;
use Fapi\FapiClientTests\MockHttpClients\FapiClientApiTokensMockHttpClient;
use Fapi\HttpClient\CapturingHttpClient;
use Fapi\HttpClient\GuzzleHttpClient;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/MockHttpClients/FapiClientApiTokensMockHttpClient.php';

class FapiClientApiTokensTest extends TestCase
{

	/** @var bool */
	private $generateMockHttpClient = false;

	/** @var CapturingHttpClient|FapiClientApiTokensMockHttpClient */
	private $httpClient;

	/** @var FapiClient */
	private $fapiClient;

	protected function setUp()
	{
		Environment::lock('FapiClient', \LOCKS_DIR);

		if ($this->generateMockHttpClient) {
			$this->httpClient = new CapturingHttpClient(new GuzzleHttpClient());
		} else {
			$this->httpClient = new FapiClientApiTokensMockHttpClient();
		}

		$this->fapiClient = new FapiClient(
			'test1@slischka.cz',
			'pi120wrOyzNlb7p4iQwTO1vcK',
			'https://api.fapi.cz/',
			$this->httpClient
		);
	}

	protected function tearDown()
	{
		if (!$this->generateMockHttpClient) {
			return;
		}

		$this->httpClient->writeToPhpFile(
			__DIR__ . '/MockHttpClients/FapiClientApiTokensMockHttpClient.php',
			FapiClientApiTokensMockHttpClient::class
		);
	}

	public function testCreateGetUpdateAndDeleteApiTokens()
	{
		$apiToken = $this->fapiClient->apiTokens->create([
			'purpose' => 'Sample Token',
		]);

		Assert::type('array', $apiToken);
		Assert::type('int', $apiToken['id']);
		Assert::same('Sample Token', $apiToken['purpose']);
		Assert::type('string', $apiToken['token']);

		$apiTokens = $this->fapiClient->apiTokens->findAll([
			'purpose' => 'Sample Token',
		]);

		Assert::type('array', $apiTokens);
		Assert::count(1, $apiTokens);
		Assert::same($apiToken, $apiTokens[0]);

		$apiTokens = $this->fapiClient->apiTokens->findAll([
			'purpose' => 'xxx',
		]);

		Assert::type('array', $apiTokens);
		Assert::count(0, $apiTokens);

		Assert::same($apiToken, $this->fapiClient->apiTokens->find($apiToken['id']));

		$updatedApiToken = $this->fapiClient->apiTokens->update($apiToken['id'], [
			'purpose' => 'Updated Token',
		]);

		Assert::type('array', $updatedApiToken);
		Assert::same($apiToken['id'], $updatedApiToken['id']);
		Assert::same('Updated Token', $updatedApiToken['purpose']);

		Assert::exception(function () {
			$this->fapiClient->apiTokens->update(-1, [
				'purpose' => 'Updated Token',
			]);
		}, NotFoundException::class);

		$this->fapiClient->apiTokens->delete($apiToken['id']);
		$this->fapiClient->apiTokens->delete(-1);

		Assert::null($this->fapiClient->apiTokens->find($apiToken['id']));

		$fapiClient = $this->fapiClient;
		Assert::exception(static function () use ($fapiClient) {
			$fapiClient->apiTokens->find(1);
		}, AuthorizationException::class, 'You are not authorized for this action.');
	}

}

(new FapiClientApiTokensTest())->run();
