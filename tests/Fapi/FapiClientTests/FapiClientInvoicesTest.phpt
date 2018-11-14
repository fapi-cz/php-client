<?php
declare(strict_types = 1);

/**
 * Test: Fapi\FapiClient\FapiClient creating, getting, updating and deleting invoices.
 *
 * @testCase Fapi\FapiClientTests\FapiClientInvoicesTest
 */

namespace Fapi\FapiClientTests;

use Fapi\FapiClient\AuthorizationException;
use Fapi\FapiClient\FapiClient;
use Fapi\FapiClientTests\MockHttpClients\FapiClientInvoicesMockHttpClient;
use Fapi\HttpClient\CapturingHttpClient;
use Fapi\HttpClient\GuzzleHttpClient;
use Nette\Utils\Strings;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/MockHttpClients/FapiClientInvoicesMockHttpClient.php';

class FapiClientInvoicesTest extends TestCase
{

	/** @var bool */
	private $generateMockHttpClient = false;

	/** @var CapturingHttpClient|FapiClientInvoicesMockHttpClient */
	private $httpClient;

	/** @var FapiClient */
	private $fapiClient;

	protected function setUp()
	{
		Environment::lock('FapiClient', \LOCKS_DIR);

		if ($this->generateMockHttpClient) {
			$this->httpClient = new CapturingHttpClient(new GuzzleHttpClient());
		} else {
			$this->httpClient = new FapiClientInvoicesMockHttpClient();
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
			__DIR__ . '/MockHttpClients/FapiClientInvoicesMockHttpClient.php',
			FapiClientInvoicesMockHttpClient::class
		);
	}

	public function testCreateGetUpdateAndDeleteInvoices()
	{
		$createdInvoice = $this->fapiClient->invoices->create([
			'client' => 1104658,
			'items' => [
				[
					'name' => 'Sample Item',
					'price' => 10,
				],
			],
		]);

		Assert::type('array', $createdInvoice);
		Assert::type('int', $createdInvoice['id']);
		Assert::type('string', $createdInvoice['number']);
		Assert::same('Sample Item', $createdInvoice['items'][0]['name']);

		$invoices = $this->fapiClient->invoices->findAll([
			'limit' => 1,
		]);

		Assert::type('array', $invoices);
		Assert::type('array', $invoices[0]);
		Assert::type('int', $invoices[0]['id']);

		$invoice = $this->fapiClient->invoices->find($createdInvoice['id']);
		Assert::same($createdInvoice['id'], $invoice['id']);
		Assert::same($createdInvoice['number'], $invoice['number']);

		$invoicePdf = $this->fapiClient->invoices->getPdf($invoice['id']);
		Assert::type('string', $invoicePdf);
		Assert::true(Strings::startsWith($invoicePdf, '%PDF-1.4'));

		$updatedInvoice = $this->fapiClient->invoices->update($invoice['id'], [
			'notes' => 'Sample footer note',
		]);

		Assert::type('array', $updatedInvoice);
		Assert::same($invoice['id'], $updatedInvoice['id']);
		Assert::same('Sample footer note', $updatedInvoice['notes']);

		$this->fapiClient->invoices->delete($invoice['id']);

		Assert::null($this->fapiClient->invoices->find($invoice['id']));
		Assert::null($this->fapiClient->invoices->getPdf($invoice['id']));

		$fapiClient = $this->fapiClient;
		Assert::exception(static function () use ($fapiClient) {
			$fapiClient->invoices->find(1);
		}, AuthorizationException::class, 'You are not authorized for this action.');

		$count = $this->fapiClient->invoices->getCount([
			'user' => 3,
			'status' => 'issued',
			'created_on_from' => '2017-06-01 00:00:00',
			'created_on_to' => '2017-07-01 23:59:59',
		]);
		Assert::same(0, $count);
	}

}

(new FapiClientInvoicesTest())->run();
