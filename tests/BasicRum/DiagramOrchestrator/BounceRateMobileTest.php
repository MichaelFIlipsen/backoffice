<?php

namespace App\Tests\BasicRum\Layers\DataLayer\Query;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use App\BasicRum\CollaboratorsAggregator;
use App\BasicRum\DiagramOrchestrator;

class BounceRateMobileTest extends KernelTestCase
{

    protected function setUp()
    {
        static::bootKernel();
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     */
    private function _getDoctrine() : \Doctrine\Bundle\DoctrineBundle\Registry
    {
        return static::$kernel->getContainer()->get('doctrine');
    }

    public function testMobileFirstPaintBounceRateSelected()
    {
        $requirementsArr = [
            'filters' => [
                'device_type' => [
                    'condition'    => 'is',
                    'search_value' => 'mobile'
                ]
            ],
            'periods' => [
                [
                    'from_date' => '10/24/2018',
                    'to_date'   => '10/24/2018'
                ]
            ],
            'technical_metrics' => [
                'time_to_first_paint' => 1
            ],
            'business_metrics'  => [
                'bounce_rate'       => 1,
                'stay_on_page_time' => 1
            ]
        ];

        $collaboratorsAggregator = new CollaboratorsAggregator();

        $collaboratorsAggregator->fillRequirements($requirementsArr);

        $diagramOrchestrator = new DiagramOrchestrator(
            $collaboratorsAggregator->getCollaborators(),
            $this->_getDoctrine()
        );

        $res = $diagramOrchestrator->process();

        $this->assertEquals(
            [
                [
                    [
                        [
                            'pageViewId'      => 1,
                            'firstPageViewId' => 1,
                            'firstPaint'      => 344,
                            'pageViewsCount'  => 1,
                            'guid'            => 'ffe7ccec-c9d1-410c-a189-c166edee257f_1549190244901',
                            'stayOnPageTime'  => 24
                        ]
                    ]
                ]
            ],
            $res
        );
    }

}