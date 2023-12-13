<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2019 Marco Muths
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PhpDATest\Parser\Filter;

use PhpDA\Parser\Filter\NodeName;

class NodeNameTest extends \PHPUnit_Framework_TestCase
{
    /** @var NodeName */
    protected $fixture;

    /** @var \PhpParser\Node\Name | \Mockery\MockInterface */
    protected $entity;

    /** @var string|null */
    protected $expected = '';

    protected function setUp(): void
    {
        $this->createEntityMock();
        $this->fixture = new NodeName;
    }

    protected function createEntityMock()
    {
        $this->entity = \Mockery::mock('PhpParser\Node\Name');
    }

    protected function assertNamespaceFilter()
    {
        $nodeName = $this->fixture->filter($this->entity);
        if (!is_null($nodeName)) {
            $nodeName = $nodeName->toString();
        }

        self::assertSame($this->expected, $nodeName);
    }

    public function testAccessAggregationIndicator()
    {
        self::assertSame('slice', $this->fixture->getAggregationIndicator());
    }

    public function testUnFiltered()
    {
        $this->expected = '\Foo\Bar';
        $this->entity->parts = explode('\\', $this->expected);
        $this->entity->shouldReceive('toString')->andReturn($this->expected);
        self::assertNamespaceFilter();
    }

    public function testSelfIgnored()
    {
        $ignore = 'self';
        $this->expected = null;
        $this->entity->shouldReceive('toString')->andReturn($ignore);
        self::assertNamespaceFilter();
    }

    public function testParentIgnored()
    {
        $ignore = 'parent';
        $this->expected = null;
        $this->entity->shouldReceive('toString')->andReturn($ignore);
        self::assertNamespaceFilter();
    }

    public function testNullIgnored()
    {
        $ignore = 'NULL';
        $this->expected = null;
        $this->entity->shouldReceive('toString')->andReturn($ignore);
        self::assertNamespaceFilter();
    }

    public function testTrueIgnored()
    {
        $ignore = 'true';
        $this->expected = null;
        $this->entity->shouldReceive('toString')->andReturn($ignore);
        self::assertNamespaceFilter();
    }

    public function testFalseIgnored()
    {
        $ignore = 'False';
        $this->expected = null;
        $this->entity->shouldReceive('toString')->andReturn($ignore);
        self::assertNamespaceFilter();
    }

    public function testExcludingByDepth()
    {
        $this->fixture->setOptions(array('minDepth' => 2));

        $string = $this->expected = '\Foo\Bar';
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn($string);
        self::assertNamespaceFilter();

        $string = $this->expected = 'Foo\Bar\Baz';
        $this->createEntityMock();
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn($string);
        self::assertNamespaceFilter();

        $string = 'Foo';
        $this->expected = null;
        $this->createEntityMock();
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn($string);
        self::assertNamespaceFilter();

        $string = '';
        $this->expected = null;
        $this->createEntityMock();
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn($string);
        self::assertNamespaceFilter();
    }

    public function testExcludingByPattern()
    {
        $this->fixture->setOptions(array('excludePattern' => '%Foo%'));

        $string = 'Foo';
        $this->expected = null;
        $this->entity->shouldReceive('toString')->andReturn($string);
        self::assertNamespaceFilter();

        $string = $this->expected = 'Bar';
        $this->createEntityMock();
        $this->entity->parts = explode('\\', $this->expected);
        $this->entity->shouldReceive('toString')->andReturn($string);
        self::assertNamespaceFilter();
    }

    /**
     * @dataProvider provideSlicingWithOffsetData
     */
    public function testSlicingWithOffset_dp($givenSliceOffset, $expectedSlice): void
    {
        $string = 'Foo\\Bar\\Baz';

        $this->fixture->setOptions(array('sliceOffset' => $givenSliceOffset));

        $this->expected = $expectedSlice;
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn($string);

        self::assertNamespaceFilter();
    }

    public static function provideSlicingWithOffsetData()
    {
        yield [0, 'Foo\\Bar\\Baz'];
        yield [2, 'Baz'];
    }

    /**
     * @dataProvider provideSlicingWithLengthData
     */
    public function testSlicingWithLength($givenSliceLength, $expectedSlice): void
    {
        $string = 'Foo\\Bar\\Baz';

        $this->fixture->setOptions(array('sliceLength' => $givenSliceLength));

        $this->expected = $expectedSlice;
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn($string);

        self::assertNamespaceFilter();
    }

    public static function provideSlicingWithLengthData()
    {
        yield [0, 'Foo\\Bar\\Baz'];
        yield [2, 'Foo\\Bar'];
    }

    /**
     * @dataProvider provideSlicingWithOffsetAndLengthData
     */
    public function testSlicingWithOffsetAndLength($givenOptions, $expectedSlice): void
    {

        $this->fixture->setOptions($givenOptions);

        $string = 'Foo\Bar\Baz';

        $this->expected = $expectedSlice;
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn($string);
        self::assertNamespaceFilter();
    }

    public static function provideSlicingWithOffsetAndLengthData()
    {
        $givenOptions = array('sliceLength' => 2, 'sliceOffset' => 1);
        $expectedSlice = 'Bar\\Baz';
        yield [$givenOptions, $expectedSlice];

        $givenOptions = array('sliceLength' => 0, 'sliceOffset' => 0);
        $expectedSlice = 'Foo\\Bar\\Baz';
        yield [$givenOptions, $expectedSlice];
    }

    public function testSlicingToNull()
    {
        $this->fixture->setOptions(array('sliceLength' => 2, 'sliceOffset' => 1));

        $string = '';
        $this->expected = null;
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn($string);
        self::assertNamespaceFilter();
    }

    public function testFilteringNamespace()
    {
        $filter = \Mockery::mock('PhpDA\Parser\Filter\NamespaceFilterInterface');
        $this->fixture->setOptions(array('namespaceFilter' => $filter));

        $string = 'Foo\Bar\Baz';
        $this->expected = 'Baz\Foo';
        $this->entity->parts = explode('\\', $string);
        $this->entity->shouldReceive('toString')->andReturn('Baz\Foo');
        $filter->shouldReceive('filter')->with($this->entity->parts)->andReturn(array('Baz', 'Foo'));
        self::assertNamespaceFilter();
    }
}
