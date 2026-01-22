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

namespace BaksDev\Drom\Products\Controller\Admin;

use BaksDev\Drom\Products\Entity\DromProduct;
use BaksDev\Drom\Products\Repository\DromProductProfile\DromProductProfileInterface;
use BaksDev\Drom\Products\Repository\OneProductWithDromImages\OneProductWithDromImagesInterface;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductDTO;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductForm;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductHandler;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_DROM_PRODUCTS_EDIT')]
final class NewEditController extends AbstractController
{
    /** @throws JsonException */
    #[Route(
        '/admin/drom/product/{product}/{offer}/{variation}/{modification}',
        name: 'admin.products.edit',
        methods: ['GET', 'POST']
    )]
    public function index(
        Request $request,
        DromProductProfileInterface $DromProductProfileInterface,
        DromProductHandler $DromProductHandler,
        OneProductWithDromImagesInterface $OneProductWithDromImages,
        #[ParamConverter(ProductUid::class)] $product,
        #[ParamConverter(ProductOfferConst::class)] ?ProductOfferConst $offer = null,
        #[ParamConverter(ProductVariationConst::class)] ?ProductVariationConst $variation = null,
        #[ParamConverter(ProductModificationConst::class)] ?ProductModificationConst $modification = null,
    ): Response
    {

        $dromProductDTO = new DromProductDTO();

        $dromProductDTO
            ->setProduct($product)
            ->setOffer($offer)
            ->setVariation($variation)
            ->setModification($modification);

        $dromProductDTO
            ->getProfile()
            ->setValue($this->getProfileUid());
        
        $dromProductDTO
            ->getKit()
            ->setValue((int) $request->get('kit'));

        /**
         * Находим уникальный продукт Drom, делаем его инстанс, передаем в форму
         * @var DromProduct|false $dromProductCard
         */
        $dromProductCard = $DromProductProfileInterface
            ->product($dromProductDTO->getProduct())
            ->offerConst($dromProductDTO->getOffer())
            ->variationConst($dromProductDTO->getVariation())
            ->modificationConst($dromProductDTO->getModification())
            ->kit($dromProductDTO->getKit()->getValue())
            ->find();

        if(true === ($dromProductCard instanceof DromProduct))
        {
            $dromProductCard->getDto($dromProductDTO);
        }

        $form = $this->createForm(
            DromProductForm::class,
            $dromProductDTO,
            ['action' => $this->generateUrl(
                'drom-products:admin.products.edit',
                [
                    'product' => $dromProductDTO->getProduct(),
                    'offer' => $dromProductDTO->getOffer(),
                    'variation' => $dromProductDTO->getVariation(),
                    'modification' => $dromProductDTO->getModification(),
                    'kit' => $dromProductDTO->getKit()->getValue(),
                    'page' => $request->get('page'),
                ],
            )],
        );

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('drom_product'))
        {
            $this->refreshTokenForm($form);

            $handle = $DromProductHandler->handle($dromProductDTO);

            $this->addFlash(
                'page.edit',
                $handle instanceof DromProduct ? 'success.edit' : 'danger.edit',
                'drom-products.admin',
                $handle,
            );

            return $this->redirectToRoute(
                route: 'drom-products:admin.products.index',
                parameters: ['page' => $request->get('page')],
            );
        }

        $product = $OneProductWithDromImages
            ->product($dromProductDTO->getProduct())
            ->offerConst($dromProductDTO->getOffer())
            ->variationConst($dromProductDTO->getVariation())
            ->modificationConst($dromProductDTO->getModification())
            ->find();

        if(false === $product)
        {
            throw new InvalidArgumentException('Продукт не найден ');
        }

        return $this->render(['form' => $form->createView(), 'product' => $product]);
    }
}
