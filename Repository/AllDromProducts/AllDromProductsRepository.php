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

namespace BaksDev\Drom\Products\Repository\AllDromProducts;

use BaksDev\Drom\Products\Entity\DromProduct;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use Generator;

final class AllDromProductsRepository implements AllDromProductsInterface
{
    private ProductUid|false $product = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function product(Product|ProductUid|string $product): self
    {
        if($product instanceof Product)
        {
            $product = $product->getId();
        }

        if(is_string($product))
        {
            $product = new ProductUid($product);
        }

        $this->product = $product;

        return $this;
    }

    /**
     * Возвращает данные карточек продукта Drom
     * @return Generator<AllDromProductsResult>
     */
    public function findAll(): Generator
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class);

        $dbal->from(DromProduct::class, 'drom_product');

        if($this->product !== false)
        {
            $dbal
                ->where('drom_product.product = :product')
                ->setParameter('product', $this->product, ProductUid::TYPE);
        }

        $dbal->addSelect('drom_product.id as id');
        $dbal->addSelect('drom_product.product as product');
        $dbal->addSelect('drom_product.offer as offer');
        $dbal->addSelect('drom_product.variation as variation');
        $dbal->addSelect('drom_product.modification as modification');

        return $dbal->fetchAllHydrate(AllDromProductsResult::class);
    }
}
