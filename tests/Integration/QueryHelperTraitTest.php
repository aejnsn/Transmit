<?php

namespace NavJobs\Transmit\Test\Integration;

use League\Fractal\ParamBag;
use NavJobs\Transmit\Traits\QueryHelperTrait;

class QueryHelperTraitTest extends TestCase
{
    use QueryHelperTrait;

    /**
     * @test
     */
    public function it_applies_parameters_to_a_query_builder()
    {
        $builder = new TestBuilder();

        $builder = $this->applyParameters($builder, new ParamBag([
            'limit' => '5',
            'offset' => '2',
            'sort' => '-created_at'
        ]));

        $this->assertEquals(5, $builder->getLimit());
        $this->assertEquals(2, $builder->getOffset());
        $this->assertEquals('created_at', $builder->getSort());
        $this->assertEquals('desc', $builder->getOrder());
    }

    /**
     * @test
     */
    public function it_just_returns_the_builder_if_no_parameters_specified()
    {
        $builder = new TestBuilder('');

        $builder = $this->applyParameters($builder);

        $this->assertEquals(null, $builder->getLimit());
        $this->assertEquals(null, $builder->getOffset());
    }
}
