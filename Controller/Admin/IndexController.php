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

use BaksDev\Drom\Products\Forms\DromFilter\DromProductsFilterDTO;
use BaksDev\Drom\Products\Forms\DromFilter\DromProductsFilterForm;
use BaksDev\Drom\Products\Repository\AllProductsWithDromImage\AllProductsWithDromImagesInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_DROM_PRODUCTS_INDEX')]
final class IndexController extends AbstractController
{
    #[Route('/admin/drom/products/{page<\d+>}', name: 'admin.products.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AllProductsWithDromImagesInterface $AllProductsWithDromImages,
        int $page = 0,
    ): Response
    {
        /** Поиск */
        $search = new SearchDTO();

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('drom-products:admin.products.index')],
            )
            ->handleRequest($request);

        /** Фильтр продукции по ТП */
        $productFilterDTO = new ProductFilterDTO();

        $productFilterForm = $this
            ->createForm(
                type: ProductFilterForm::class,
                data: $productFilterDTO,
                options: ['action' => $this->generateUrl('drom-products:admin.products.index')],
            )
            ->handleRequest($request);

        /** Фильтр продукции по фото|без фото */
        $dromProductsFilterDTO = new DromProductsFilterDTO();
        $dromProductsFilterForm = $this
            ->createForm(
                type: DromProductsFilterForm::class,
                data: $dromProductsFilterDTO,
                options: ['action' => $this->generateUrl('drom-products:admin.products.index')],
            )
            ->handleRequest($request);


        $products = $AllProductsWithDromImages
            ->search($search)
            ->filter($productFilterDTO)
            ->filterDromProducts($dromProductsFilterDTO)
            ->findPaginator();

        return $this->render(
            [
                'filter' => $productFilterForm->createView(),
                'drom' => $dromProductsFilterForm->createView(),
                'search' => $searchForm->createView(),
                'query' => $products
            ],
        );
    }
}
