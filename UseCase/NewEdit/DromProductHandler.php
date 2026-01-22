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

namespace BaksDev\Drom\Products\UseCase\NewEdit;

use BaksDev\Drom\Products\Entity\DromProduct;
use BaksDev\Drom\Products\Entity\Images\DromProductImage;
use BaksDev\Drom\Products\Messenger\DromProductMessage;
use BaksDev\Drom\Products\UseCase\NewEdit\Images\DromProductImagesDTO;
use BaksDev\Core\Entity\AbstractHandler;

final class DromProductHandler extends AbstractHandler
{
    public function handle(DromProductDTO $command): string|DromProduct
    {
        /** Добавляем command для валидации и гидрации */
        $this->setCommand($command);

        /** @var DromProduct $entity */
        $entity = $this
            ->prePersistOrUpdate(
                DromProduct::class,
                [
                    'id' => $command->getDromProductUid(),
                ],
            );


        /** Проверяем наличие root хотя бы у одного из загружаемых изображений */
        $hasRoot = $entity
            ->getImages()
            ->filter(function (DromProductImage $image)
            {
                return $image->getEntityDto()->getRoot();
            });


        /** Если ни одно из изображений не является root, делаем главным первое из коллекции */
        if($hasRoot->isEmpty())
        {
            $firstImage = $entity->getImages()->first();
            if(false === empty($firstImage))
            {
                $firstImage->getEntityDto()->setRoot(true);
            }
        }


        /**
         * Загружаем изображения
         * @var DromProductImage $image
         */
        foreach($entity->getImages() as $image)
        {
            /** @var DromProductImagesDTO $dromImagesDTO */
            if($dromImagesDTO = $image->getEntityDto())
            {
                if(null !== $dromImagesDTO->getFile())
                {
                    $this->imageUpload->upload($dromImagesDTO->getFile(), $image);
                }
            }
        }

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();

        $this->messageDispatch
            ->addClearCacheOther('drom-board')
            ->dispatch(
                message: new DromProductMessage($entity->getId()),
                transport: 'drom-products',
            );

        return $entity;
    }
}
