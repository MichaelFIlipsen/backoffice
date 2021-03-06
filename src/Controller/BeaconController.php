<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\BasicRum\WaterfallSvgRenderer;

use App\BasicRum\ResourceSize;

use App\Entity\NavigationTimings;
use App\Entity\NavigationTimingsUserAgents;

use App\BasicRum\Beacon\RumData\ResourceTiming;

class BeaconController extends AbstractController
{

    /**
     * @Route("/diagrams/beacon/draw", name="diagrams_beacon_draw")
     */
    public function draw()
    {
        $pageViewId = (int) $_POST['page_view_id'];

        /** @var NavigationTimings $navigationTiming */
        $navigationTiming = $this->getDoctrine()
            ->getRepository(NavigationTimings::class)
            ->findBy(['pageViewId' => $pageViewId]);

        /** @var NavigationTimingsUserAgents $userAgent */
        $userAgent = $this->getDoctrine()
            ->getRepository(NavigationTimingsUserAgents::class)
            ->findBy(['id' => $navigationTiming[0]->getUserAgentId()]);

        $sizeDistribution = [];

        $resourceTiming = new ResourceTiming();

        $resourceTimingsData = $resourceTiming->fetchResources($pageViewId, $this->getDoctrine());

        if (!empty($resourceTimingsData)) {
            $resourceSizesCalculator = new ResourceSize();
            $sizeDistribution = $resourceSizesCalculator->calculateSizes($resourceTimingsData);
        }

        $timings = [
            'nt_nav_st'      => 0,
            'nt_first_paint' => $navigationTiming[0]->getFirstContentfulPaint(),
            'nt_res_st'      => $navigationTiming[0]->getFirstByte(),
            'restiming'      => $resourceTimingsData
        ];

        $renderer = new WaterfallSvgRenderer();

        $response = new Response(
            json_encode(
                [
                    'waterfall'             => $renderer->render($timings),
                    'resource_distribution' =>
                        [
                            'labels' => array_keys($sizeDistribution),
                            'values' => array_values($sizeDistribution)
                        ],
                    'user_agent'            => $userAgent[0]->getUserAgent(),
                    'browser_name'          => $userAgent[0]->getBrowserName()
                ]
            )
        );

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
