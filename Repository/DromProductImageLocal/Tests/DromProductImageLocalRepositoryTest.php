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

namespace BaksDev\Drom\Products\Repository\DromProductImageLocal\Tests;

use BaksDev\Drom\Products\Repository\DromProductImageLocal\DromProductImageLocalInterface;
use BaksDev\Drom\Products\Repository\DromProductImageLocal\DromProductImageLocalRepository;
use BaksDev\Drom\Products\Repository\DromProductImageLocal\DromProductImageLocalResult;
use BaksDev\Drom\Products\UseCase\NewEdit\Images\Tests\DromProductImagesNewTest;
use PHPUnit\Framework\Attributes\DependsOnClass;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('drom-products')]
#[Group('drom-products-repository')]
final class DromProductImageLocalRepositoryTest extends KernelTestCase
{
    #[DependsOnClass(DromProductImagesNewTest::class)]
    public function testRepository(): void
    {
        /** @var DromProductImageLocalRepository $DromProductImageLocalRepository */
        $DromProductImageLocalRepository = self::getContainer()->get(DromProductImageLocalInterface::class);

        $result = $DromProductImageLocalRepository->findAll();

        foreach ($result as $dromProductImageLocalResult)
        {
            self::assertInstanceOf(DromProductImageLocalResult::class, $dromProductImageLocalResult);

            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(DromProductImageLocalResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($dromProductImageLocalResult);
//                        dump($data);
                }
            }

            return;
        }
    }
}