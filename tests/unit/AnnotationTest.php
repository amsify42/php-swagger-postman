<?php
namespace Amsify42\TestSP;

use PHPUnit\Framework\TestCase;
use Amsify42\PhpSwaggerPostman\Swagger;

final class AnnotationTest extends TestCase
{
	public function testSamples()
	{
		$annotation = Swagger::generateAnnotation();
		$this->assertTrue(true);
		//ddd($annotation);
	}
}