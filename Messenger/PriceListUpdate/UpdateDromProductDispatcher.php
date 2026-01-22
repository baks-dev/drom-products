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

namespace BaksDev\Drom\Products\Messenger\PriceListUpdate;

use BaksDev\Core\Twig\TemplateExtension;
use BaksDev\Drom\Board\Repository\AllProductsWithMapper\AllProductsWithMapperInterface;
use BaksDev\Drom\Products\Api\Post\UpdateDromProduct\UpdateDromProductRequest;
use BaksDev\Drom\Repository\DromAuthorizationByProfile\DromAuthorizationByProfileInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;

/** Метод отправляет запрос Drom API на обновление объявления */
#[AsMessageHandler]
final readonly class UpdateDromProductDispatcher
{
    public function __construct(
        #[Target('dromProductsLogger')] private LoggerInterface $Logger,
        private UpdateDromProductRequest $UpdateDromProductRequest,
        private Environment $environment,
        private TemplateExtension $templateExtension,
        private AllProductsWithMapperInterface $AllProductsWithMappingRepository,
        private DromAuthorizationByProfileInterface $dromAuthorizationByProfileRepository,
    ) {}

    public function __invoke(UpdateDromProductMessage $message): void
    {
        $dromAuthorization = $this->dromAuthorizationByProfileRepository
            ->getAuthorization(new UserProfileUid($message->getProfile()));

        if(false === $dromAuthorization)
        {
            $this->Logger->warning(
                sprintf('Настройки профиля Drom не были найдены для профиля %s', $message->getProfile()),
                [var_export($message, true), self::class.':'.__LINE__]
            );
        }

        $AllProductsWithMapping = $this->AllProductsWithMappingRepository
            ->forProfile($message->getProfile())
            ->forProduct($message->getProduct())
            ->forOfferConst($message->getOfferConst())
            ->forVariationConst($message->getVariationConst())
            ->forModificationConst($message->getModificationConst())
            ->findAll();

        if(false === $AllProductsWithMapping)
        {
            $this->Logger->warning(
                sprintf('%s: Продукт Drom не был найден для профиля ', $message->getProfile()),
                [var_export($message, true), self::class.':'.__LINE__]
            );

            return;
        }

        $path = '@drom-board:public/export/feed/export.html.twig';
        $template = $this->templateExtension->extends($path);
        $xml = $this->environment->render($template, ['products' => '$products']);

        $update = $this->UpdateDromProductRequest
            ->setPriceListId($dromAuthorization->getPricelist())
            ->setAuth($dromAuthorization->getKey())
            ->post($xml);

        if(false === $update)
        {
            $this->Logger->critical(
                sprintf('%s: Ошибка обновления данных о продукте в прайс-листе Drom', $message->getProduct()),
                [var_export($message, true), self::class.':'.__LINE__]
            );
            return;
        }

        $this->Logger->critical(
            sprintf('%s: Данные о продукте в прайс-листе Drom успешно обновлены', $message->getProduct()),
            [var_export($message, true), self::class.':'.__LINE__]
        );
    }
}
