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

namespace BaksDev\Drom\Products\UseCase\NewEdit\Images\Tests;

use BaksDev\Drom\Products\BaksDevDromProductsBundle;
use BaksDev\Drom\Products\Entity\DromProduct;
use BaksDev\Drom\Products\Type\Id\DromProductUid;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductDTO;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductHandler;
use BaksDev\Drom\Products\UseCase\NewEdit\Images\DromProductImagesDTO;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

#[When(env: 'test')]
#[Group('drom-products')]
#[Group('drom-products-usecase')]
class DromProductImagesEditTest extends KernelTestCase
{
    #[DependsOnClass(DromProductImagesNewTest::class)]
    public function testEdit(): void
    {
        $container = self::getContainer();
        $EntityManager = $container->get(EntityManagerInterface::class);

        $dromProduct = $EntityManager
            ->getRepository(DromProduct::class)
            ->find(DromProductUid::TEST);

        self::assertNotNull($dromProduct);

        $editDTO = new DromProductDTO();

        $dromProduct->getDto($editDTO);

        /** @var DromProductImagesDTO $newImage */
        $newImage = $editDTO
            ->getImages()
            ->current();

        self::assertTrue($newImage->getRoot());
        self::assertSame('jpg', $newImage->getExt());

        $editDTO->removeImage($newImage);
        self::assertEmpty($editDTO->getImages());


        $fileSystem = $container->get(Filesystem::class);

        /** @var ContainerBagInterface $containerBag */
        $containerBag = $container->get(ContainerBagInterface::class);

        /** Создаем путь к тестовой директории */
        $testUploadDir = implode(
            DIRECTORY_SEPARATOR,
            [$containerBag->get('kernel.project_dir'), 'public', 'upload', 'tests']
        );

        /** Проверяем существование директории для тестовых картинок */
        if(false === is_dir($testUploadDir))
        {
            $fileSystem->mkdir($testUploadDir);
        }

        /** Новая картинка PNG */
        $editImagePNG = new DromProductImagesDTO();
        $editImagePNG->setRoot(false);

        /** Файл из пакета для копирования в тестовую директорию */
        $pngFrom = new File(BaksDevDromProductsBundle::PATH.'Resources/tests/PNG.png', true);

        /** Файл для записи в тестовой директории */
        $pngTo = new File($testUploadDir.'/PNG.png', false);

        /** Копируем файл из пакета для копирования в тестовую директорию */
        $fileSystem->copy($pngFrom->getPathname(), $pngTo->getPathname());

        self::assertTrue(
            is_file($pngTo->getPathname()),
            'Не удалось создать файл в тестовой директории по пути:'.$pngTo->getPathname()
        );

        $editImagePNG->setFile($pngTo);

        $editDTO->addImage($editImagePNG);

        /** Новая картинка WEBP */
        $editImageWEBP = new DromProductImagesDTO();
        $editImageWEBP->setRoot(true);

        /** Файл из пакета для копирования в тестовую директорию */
        $webpFrom = new File(BaksDevDromProductsBundle::PATH.'Resources/tests/WEBP.webp', true);

        /** Файл для записи в тестовой директории */
        $webpTo = new File($testUploadDir.'/WEBP.webp', false);

        /** Копируем файл из пакета для копирования в тестовую директорию */
        $fileSystem->copy($webpFrom->getPathname(), $webpTo->getPathname());

        self::assertTrue(
            is_file($webpTo->getPathname()),
            'Не удалось создать файл в тестовой директории по пути:'.$webpTo->getPathname()
        );

        $editImagePNG->setFile($webpTo);

        $editDTO->addImage($editImageWEBP);

        /** @var DromProductHandler $Handler */
        $Handler = $container->get(DromProductHandler::class);
        $editDromProduct = $Handler->handle($editDTO);
        self::assertTrue($editDromProduct instanceof DromProduct);
    }
}
