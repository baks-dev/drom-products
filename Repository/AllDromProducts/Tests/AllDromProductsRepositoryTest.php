<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Drom\Products\Repository\AllDromProducts\Tests;

use BaksDev\Drom\Products\Repository\AllDromProducts\AllDromProductsInterface;
use BaksDev\Drom\Products\Repository\AllDromProducts\AllDromProductsRepository;
use BaksDev\Drom\Products\Repository\AllDromProducts\AllDromProductsResult;
use BaksDev\Drom\Products\UseCase\NewEdit\Tests\DromProductNewTest;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('drom-products')]
#[Group('drom-products-repository')]
final class AllDromProductsRepositoryTest extends KernelTestCase
{
    #[DependsOnClass(DromProductNewTest::class)]
    public function testRepositoryProduct(): void
    {
        /** @var AllDromProductsRepository $AllDromProductsRepository */
        $AllDromProductsRepository = self::getContainer()->get(AllDromProductsInterface::class);

        $result = $AllDromProductsRepository
            ->product(new ProductUid(ProductUid::TEST))
            ->findAll();

        foreach ($result as $allDromProductsResult) {
            self::assertInstanceOf(AllDromProductsResult::class, $allDromProductsResult);

            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(AllDromProductsResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($allDromProductsResult);
//                        dump($data);
                }
            }

            return;
        }
    }
    
    public function testRepositoryEvent(): void
    {
        /** @var AllDromProductsRepository $AllDromProductsRepository */
        $AllDromProductsRepository = self::getContainer()->get(AllDromProductsInterface::class);

        $result = $AllDromProductsRepository
            ->event(ProductEventUid::TEST)
            ->findAll();

        foreach ($result as $allDromProductsResult) {
            self::assertInstanceOf(AllDromProductsResult::class, $allDromProductsResult);

            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(AllDromProductsResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($allDromProductsResult);
//                        dump($data);
                }
            }

            return;
        }

        self::assertTrue(true);
    }
}