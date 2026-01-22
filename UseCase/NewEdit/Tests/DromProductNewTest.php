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
use BaksDev\Drom\Products\Entity\Images\DromProductImage;
use BaksDev\Drom\Products\Type\Id\DromProductUid;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductDTO;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductHandler;
use BaksDev\Drom\Products\UseCase\NewEdit\Images\DromProductImagesDTO;
use BaksDev\Drom\UseCase\Admin\NewEdit\Tests\DromTokenNewTest;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Tests\ProductsProductNewAdminUseCaseTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('drom-products')]
#[Group('drom-products-controller')]
#[Group('drom-products-repository')]
#[Group('drom-products-usecase')]
final class DromProductNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $EntityManager */
        $EntityManager = $container->get(EntityManagerInterface::class);

        $dromProduct = $EntityManager
            ->getRepository(DromProduct::class)
            ->find(DromProductUid::TEST);

        if($dromProduct)
        {
            $EntityManager->remove($dromProduct);
        }

        $dromProductImages = $EntityManager->getRepository(DromProductImage::class)
            ->findBy(['drom' => DromProductUid::TEST]);

        foreach($dromProductImages as $image)
        {
            $EntityManager->remove($image);
        }

        $EntityManager->flush();
        $EntityManager->clear();

        /** Создаем тестовый продукт */
        ProductsProductNewAdminUseCaseTest::setUpBeforeClass();
        new ProductsProductNewAdminUseCaseTest('')->testUseCase();

        /** Создаем тестовый токен Drom */
        DromTokenNewTest::setUpBeforeClass();
        new DromTokenNewTest('')->testNew();
    }

    public function testNew(): void
    {
        $dromProductDTO = new DromProductDTO();

        $dromProductDTO->setProduct(new ProductUid(ProductUid::TEST));
        self::assertTrue($dromProductDTO->getProduct()->equals(ProductUid::TEST));

        $dromProductDTO->setOffer(new ProductOfferConst(ProductOfferConst::TEST));
        self::assertTrue($dromProductDTO->getOffer()->equals(ProductOfferConst::TEST));

        $dromProductDTO->setVariation(new ProductVariationConst(ProductVariationConst::TEST));
        self::assertTrue($dromProductDTO->getVariation()->equals(ProductVariationConst::TEST));

        $dromProductDTO->setModification(new ProductModificationConst(ProductModificationConst::TEST));
        self::assertTrue($dromProductDTO->getModification()->equals(ProductModificationConst::TEST));

        $dromProductDTO->setDescription('new_description');
        self::assertSame('new_description', $dromProductDTO->getDescription());

        $dromProductDTO->getProfile()->setValue(new UserProfileUid(UserProfileUid::TEST));
        self::assertTrue($dromProductDTO->getProfile()->getValue()->equals(UserProfileUid::TEST));

        $image = new DromProductImagesDTO();
        $dromProductDTO->getImages()->add($image);

        $container = self::getContainer();

        /** @var DromProductHandler $DromProductHandler */
        $DromProductHandler = $container->get(DromProductHandler::class);
        $newDromProduct = $DromProductHandler->handle($dromProductDTO);
        self::assertTrue($newDromProduct instanceof DromProduct, message: (string) $newDromProduct);
    }
}
