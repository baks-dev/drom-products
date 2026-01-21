<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Drom\Products\Messenger\Stocks;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Drom\Products\Messenger\PriceListUpdate\UpdateDromProductMessage;
use BaksDev\Drom\Products\Repository\AllDromProducts\AllDromProductsInterface;
use BaksDev\Drom\Repository\AllUserProfilesByActiveToken\AllUserProfilesByActiveTokenInterface;
use BaksDev\Products\Product\Messenger\Price\UpdateMarketplacePriceMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateDromProductPriceDispatcher
{
    public function __construct(
        #[Target('dromProductsLogger')] private LoggerInterface $Logger,
        private MessageDispatchInterface $MessageDispatch,
        private AllDromProductsInterface $DromProductsRepository,
        private AllUserProfilesByActiveTokenInterface $AllUserProfilesByActiveToken,
        private DeduplicatorInterface $Deduplicator,
    ) {}

    /** Обновление цены и/или количества  */
    public function __invoke(UpdateMarketplacePriceMessage $message): void
    {
        $Deduplicator = $this->Deduplicator
            ->namespace('drom-products')
            ->expiresAfter('5 minutes')
            ->deduplication([
                (string) $message->getProductEvent(),
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** Находим все карточки продуктов Drom по продукту */
        $dromProducts = $this->DromProductsRepository
            ->event($message->getProductEvent())
            ->findAll();

        foreach($dromProducts as $dromProduct)
        {
            /** Получаем все активные профили, у которых активный токен Drom */
            $profiles = $this->AllUserProfilesByActiveToken->findProfilesByActiveToken();

            if($profiles->valid() === false)
            {
                $this->Logger->warning(
                    'Не были найдены активные токены Drom',
                    [var_export($message, true), self::class.':'.__LINE__]
                );
                return;
            }

            foreach($profiles as $profile) {
                $updateDromProductStockMessage = new UpdateDromProductMessage(
                    $profile,
                    $dromProduct->getProduct(),
                    $dromProduct->getOfferConst(),
                    $dromProduct->getVariationConst(),
                    $dromProduct->getModificationConst(),
                );

                $this->MessageDispatch->dispatch(
                    message: $updateDromProductStockMessage,
                    stamps: [new MessageDelay('5 seconds')],
                    transport: 'drom-products'
                );
            }
        }

    }
}