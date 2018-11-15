<?php
declare(strict_types = 1);

namespace Fapi\FapiClient\EndPoints;

use Fapi\FapiClient\Rest\FapiRestClient;

final class Statistics
{

	/** @var FapiRestClient */
	private $restClient;

	public function __construct(FapiRestClient $restClient)
	{
		$this->restClient = $restClient;
	}

	/**
	 * @param mixed[] $parameters
	 * @return mixed[]
	 */
	public function getTotalStatistics(array $parameters): array
	{
		return $this->restClient->getTotalStatistics($parameters);
	}

}