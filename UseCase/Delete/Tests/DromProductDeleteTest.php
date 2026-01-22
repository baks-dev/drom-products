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

namespace BaksDev\Drom\Products\UseCase\Delete\Tests;

use BaksDev\Drom\Products\Entity\DromProduct;
use BaksDev\Drom\Products\Type\Id\DromProductUid;
use BaksDev\Drom\Products\UseCase\Delete\DromProductDeleteDTO;
use BaksDev\Drom\Products\UseCase\Delete\DromProductDeleteHandler;
use BaksDev\Drom\Products\UseCase\NewEdit\Images\Tests\DromProductImagesEditTest;
use Baksdev\Drom\Products\UseCase\UpdateDescription\Tests\UpdateDromProductsDescriptionHandlerTest;
use BaksDev\Drom\UseCase\Admin\Delete\Tests\DromTokenDeleteTest;
use BaksDev\Products\Product\UseCase\Admin\Delete\Tests\ProductsProductDeleteAdminUseCaseTest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;

#[When(env: 'test')]
#[Group('drom-products')]
#[Group('drom-products-usecase')]
class DromProductDeleteTest extends KernelTestCase
{
    #[DependsOnClass(DromProductImagesEditTest::class)]
    #[DependsOnClass(UpdateDromProductsDescriptionHandlerTest::class)]
    public function testDelete(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $dromProduct = $em
            ->getRepository(DromProduct::class)
            ->find(DromProductUid::TEST);

        self::assertNotNull($dromProduct);

        $deleteDTO = new DromProductDeleteDTO();

        $dromProduct->getDto($deleteDTO);

        /** @var DromProductDeleteHandler $Handler */
        $Handler = $container->get(DromProductDeleteHandler::class);
        $deletedDromProduct = $Handler->handle($deleteDTO);
        self::assertTrue($deletedDromProduct instanceof DromProduct);
    }


    public static function tearDownAfterClass(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $product = $em
            ->getRepository(DromProduct::class)
            ->find(DromProductUid::TEST);

        /** Удаляем тестовый продукт Drom */
        if($product)
        {
            $em->remove($product);

        }

        $em->flush();
        $em->clear();

        $fileSystem = $container->get(Filesystem::class);

        /** @var ContainerBagInterface $containerBag */
        $containerBag = $container->get(ContainerBagInterface::class);

        /** Создаем путь к тестовой директории */
        $testUploadDir = implode(
            DIRECTORY_SEPARATOR,
            [$containerBag->get('kernel.project_dir'), 'public', 'upload', 'tests']
        );

        /** Проверяем существование директории для тестовых картинок*/
        if(true === is_dir($testUploadDir))
        {
            $fileSystem->remove($testUploadDir);
        }

        
        /** Удаляем тестовый продукт после завершения */
        ProductsProductDeleteAdminUseCaseTest::tearDownAfterClass();


        /** Удаляем тестовый токен Drom */
        DromTokenDeleteTest::tearDownAfterClass();
    }
}
