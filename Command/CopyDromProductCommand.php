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

namespace BaksDev\Drom\Products\Command;

use BaksDev\Drom\Products\Entity\DromProduct;
use BaksDev\Drom\Products\Entity\Profile\DromProductProfile;
use BaksDev\Drom\Products\Repository\DromProductProfile\DromProductProfileInterface;
use BaksDev\Drom\Products\Type\Id\DromProductUid;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductDTO;
use BaksDev\Drom\Products\UseCase\NewEdit\DromProductHandler;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'baks:drom-products:copy', description: 'Команда копирует продукты Drom для другого профиля')]
final class CopyDromProductCommand extends Command
{
    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly DromProductProfileInterface $DromProductProfileRepository,
        private readonly DromProductHandler $DromProductHandler
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $progressBar = new ProgressBar($output);
        $progressBar->start();

        /** Обрабатываем файлы по базе данных */

        /** Указываем свои идентификаторы исходного и конечного профилей */
        $OriginalUserProfileUid = new UserProfileUid('01941715-9d2a-7d23-8bef-2f7dbc98331a');
        $CopyUserProfileUid = new UserProfileUid('0188a9a8-7508-7b3e-a0a1-312e03f7bdd9');

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('main')
            ->from(DromProduct::class, 'main');

        $orm
            ->join(
                DromProductProfile::class,
                'profile',
                'WITH',
                'profile.drom = main.id AND profile.value = :original',
            )
            ->setParameter(
                key: 'original',
                value: $OriginalUserProfileUid,
                type: UserProfileUid::TYPE,
            );

        $DromProducts = $orm->getResult();


        /** @var DromProduct $DromProduct */
        foreach($DromProducts as $DromProduct)
        {

            $DromProductDTO = new DromProductDTO();
            $DromProduct->getDto($DromProductDTO);

            /** Определяем имеющуюся настройку  */
            $DromProduct = $this->DromProductProfileRepository
                ->product($DromProductDTO->getProduct())
                ->offerConst($DromProductDTO->getOffer())
                ->variationConst($DromProductDTO->getVariation())
                ->modificationConst($DromProductDTO->getModification())
                ->kit($DromProductDTO->getKit()->getValue())
                ->forProfile($CopyUserProfileUid)
                ->find();

            if($DromProduct instanceof DromProduct)
            {
                $DromProductDTO->setId($DromProduct->getId());
            }
            else
            {
                $DromProductDTO->setId(new DromProductUid());
            }

            /** Присваиваем идентификатор профиля */
            $DromProductDTO->getProfile()->setValue($CopyUserProfileUid);

            /** Указываем нужное описание для всхе товаров */
            $Description = 'test';

            $DromProductDTO->setDescription($Description);

            $this->DromProductHandler->handle($DromProductDTO);

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->success('Команда успешно завершена');

        return Command::SUCCESS;
    }
}
