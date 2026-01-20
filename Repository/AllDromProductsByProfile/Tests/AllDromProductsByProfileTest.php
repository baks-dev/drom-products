<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Drom\Products\Repository\AllDromProductsByProfile\Tests;

use BaksDev\Drom\Products\Entity\DromProduct;
use BaksDev\Drom\Products\Repository\AllDromProductsByProfile\AllDromProductsByProfileInterface;
use BaksDev\Drom\Products\Repository\AllDromProductsByProfile\AllDromProductsByProfileRepository;
use BaksDev\Drom\Products\UseCase\NewEdit\Tests\DromProductNewTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('drom-products')]
#[Group('drom-products-repository')]
final class AllDromProductsByProfileTest extends KernelTestCase
{
    #[DependsOnClass(DromProductNewTest::class)]
    public function testRepository(): void
    {
        /** @var AllDromProductsByProfileRepository $AllDromProductsByProfileRepository */
        $AllDromProductsByProfileRepository = self::getContainer()->get(AllDromProductsByProfileInterface::class);

        $result = $AllDromProductsByProfileRepository->findAll(new UserProfileUid(UserProfileUid::TEST));

        foreach ($result as $item)
        {
            self::assertInstanceOf(DromProduct::class, $item);

            return;
        }
    }
}