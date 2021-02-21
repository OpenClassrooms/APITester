<?php

namespace OpenAPITesting\Controller;

use OpenAPITesting\Gateways\OpenAPI\OpenAPIRepository;
use OpenAPITesting\Models\Test\TestPlan;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    private OpenAPIRepository $openAPIFileLoader;

    public function __construct(OpenAPIRepository $openAPIFileLoader)
    {
        $this->openAPIFileLoader = $openAPIFileLoader;
    }

    /**
     * @Route("/test")
     */
    public function homepage()
    {
        return $this->render('testplan.html.twig', ['testPlan' => new TestPlan()]);

        return $this->render('homepage.html.twig',);
    }
}