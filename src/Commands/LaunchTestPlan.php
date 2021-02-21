<?php

namespace OpenAPITesting\Commands;

use OpenAPITesting\Models\Test\TestPlan;
use OpenAPITesting\Services\Test\ExecuteTestPlan;
use OpenAPITesting\Services\Test\ExecuteTestPlanRequest;
use OpenAPITesting\Services\Test\PrepareTestPlan;
use OpenAPITesting\Services\Test\PrepareTestPlanRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

class LaunchTestPlan extends Command
{
    protected ExecuteTestPlan $executeTestPlan;

    protected PrepareTestPlan $prepareTestPlan;

    protected Environment $templateEngine;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $testPlan = $this->prepareTestPlan($input->getOptions());
        $testPlan = $this->executeTestPlan($testPlan);
        $output = $this->renderTestPlan($testPlan, $input->getOption('format'));
    }

    protected function prepareTestPlan(array $options): TestPlan
    {
        $request = PrepareTestPlanRequest::create()
            ->openAPITitle($options['title'])
            ->version($options['version'])
            ->withFilters($options['filters'])
            ->build();

        return $this->prepareTestPlan->execute($request);
    }

    protected function executeTestPlan(TestPlan $testPlan): TestPlan
    {
        $request = ExecuteTestPlanRequest::create($testPlan)
            ->build();

        return $this->executeTestPlan->execute($request);
    }

    protected function renderTestPlan(TestPlan $testPlan, string $format): string
    {
        switch ($format) {
            case 'junit':
            case 'xml':
                $stream = $this->templateEngine->render('junit.xml.twig', ['testPlan' => $testPlan]);
                break;
            case 'html':
                $stream = $this->templateEngine->render('testplan.html.twig', ['testPlan' => $testPlan]);
                break;
        }

        return $stream;
    }
}