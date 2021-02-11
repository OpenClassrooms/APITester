<?php

namespace OpenAPITesting\Models\Test;

use Carbon\Carbon;
use cebe\openapi\spec\OpenApi;
use DateTime;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TestPlan
{
    public const STATUS_FINISHED = 'finished';

    public const STATUS_LAUNCHED = 'launched';

    public const STATUS_NOT_LAUNCHED = 'not launched';

    protected ?DateTime $finishDate = null;

    protected OpenApi $openAPI;

    /**
     * @var OperationTestCase[]
     */
    private array $operationTestCases = [];

    /**
     * @var PathTestSuite[]
     */
    protected array $pathTestSuites = [];

    protected ?DateTime $startDate = null;

    public function __construct(OpenApi $openAPI)
    {
        $this->openAPI = $openAPI;
    }

    public function getBaseUri(): string
    {
        return reset($this->openAPI->servers)->url;
    }

    /**
     * @return OperationTestCase[]
     */
    public function launch(): array
    {
        $this->startDate = Carbon::now();

        return $this->getOperationTestCases();
    }

    /**
     * @return OperationTestCase[]
     */
    public function getOperationTestCases(): array
    {
        $this->operationTestCases = [];
        foreach ($this->pathTestSuites as $pathTestSuite) {
            foreach ($pathTestSuite->getOperationTestSuites() as $operationTestSuite) {
                $this->operationTestCases = array_merge($this->operationTestCases, $operationTestSuite->getTestCases());
            }
        }

        return $this->operationTestCases;
    }

    /**
     * @param ResponseInterface[][] $responses
     */
    public function finish(array $responses)
    {
        if ($this->getStatus() !== self::STATUS_LAUNCHED) {
            throw new InvalidStatusException($this->getStatus());
        }
        foreach ($responses as $operationTestCaseId => $operationTestCaseResponses) {
            $this->operationTestCases[$operationTestCaseId]->finish($operationTestCaseResponses);
        }
        $this->finishDate = Carbon::now();
    }

    private function getStatus(): string
    {
        if ($this->finishDate !== null) {
            return self::STATUS_FINISHED;
        }
        if ($this->startDate !== null) {
            return self::STATUS_LAUNCHED;
        }

        return self::STATUS_NOT_LAUNCHED;
    }
}