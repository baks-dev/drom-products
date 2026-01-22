<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Drom\Products\UseCase\NewEdit\Tests;

use BaksDev\Drom\Products\Entity\DromProduct;
use BaksDev\Drom\Products\Type\Id\DromProductUid;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductDTO;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('drom-products')]
#[Group('drom-products-repository')]
#[Group('drom-products-usecase')]
class DromProductEditTest extends KernelTestCase
{
    #[DependsOnClass(DromProductNewTest::class)]
    public function testEdit(): void
    {
        $container = self::getContainer();
        $Em = $container->get(EntityManagerInterface::class);

        /** @var DromProduct $product */
        $product = $Em
            ->getRepository(DromProduct::class)
            ->find(DromProductUid::TEST);

        self::assertNotNull($product);

        $editDTO = new DromProductDTO();

        $product->getDto($editDTO);

        $editDTO->setDescription('edit_description');
        self::assertSame('edit_description', $editDTO->getDescription());

        /** @var DromProductHandler $Handler */
        $Handler = $container->get(DromProductHandler::class);
        $editDromProduct = $Handler->handle($editDTO);
        self::assertTrue($editDromProduct instanceof DromProduct);
    }
}
