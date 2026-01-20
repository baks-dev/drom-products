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

namespace BaksDev\Drom\Products\Messenger\Orders;

use BaksDev\Drom\Products\Messenger\PriceListUpdate\UpdateDromProductMessage;
use BaksDev\Drom\Repository\AllUserProfilesByActiveToken\AllUserProfilesByActiveTokenInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Обновляем остатки Drom при изменении статусов заказов */
#[AsMessageHandler(priority: 90)]
final readonly class UpdateStocksDromWhenChangeOrderStatusDispatcher
{
    public function __construct(
        #[Target('dromProductsLogger')] private LoggerInterface $Logger,
        private MessageDispatchInterface $messageDispatch,
        private AllUserProfilesByActiveTokenInterface $allUserProfilesByActiveToken,
        private CurrentOrderEventInterface $currentOrderEvent,
        private CurrentProductIdentifierByEventInterface $currentProductIdentifier,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        /** Получаем все активные профили, у которых активный токен Drom */
        $profiles = $this->allUserProfilesByActiveToken->findProfilesByActiveToken();

        if($profiles->valid() === false)
        {
            $this->Logger->warning('Не были найдены активные токены Drom');
            return;
        }

        /** Получаем активное событие заказа */
        $orderEvent = $this->currentOrderEvent
            ->forOrder($message->getId())
            ->find();

        if($orderEvent === false)
        {
            $this->Logger->warning(sprintf('Заказ %s не был найден', $message->getId()));
            return;
        }

        $editOrderDTO = new EditOrderDTO();
        $orderEvent->getDto($editOrderDTO);

        foreach($profiles as $profile)
        {
            /** @var OrderProductDTO $product */
            foreach($editOrderDTO->getProduct() as $product)
            {
                /** Получаем идентификаторы продуктов, на которые поступил заказ */
                $CurrentProductIdentifier = $this->currentProductIdentifier
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                if(false === ($CurrentProductIdentifier instanceof CurrentProductIdentifierResult))
                {
                    $this->Logger->warning(sprintf('Заказ %s не был найден', $product->getProduct()));
                    continue;
                }

                $updateDromProductStockMessage = new UpdateDromProductMessage(
                    $profile,
                    $CurrentProductIdentifier->getProduct(),
                    $CurrentProductIdentifier->getOfferConst(),
                    $CurrentProductIdentifier->getVariationConst(),
                    $CurrentProductIdentifier->getModificationConst(),
                );

                $this->messageDispatch->dispatch(
                    message: $updateDromProductStockMessage,
                    stamps: [new MessageDelay('5 seconds')], // задержка 5 сек для обновления остатков в объявлении на Drom
                    transport: 'drom-products'
                );
            }
        }
    }
}
