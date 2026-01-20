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

namespace BaksDev\Drom\Products\Command;

use BaksDev\Drom\Products\Entity\Images\DromProductImage;
use BaksDev\Drom\Products\Repository\DromImageIdentifierByName\DromImageIdentifierByNameInterface;
use BaksDev\Drom\Products\Repository\DromProductImageLocal\DromProductImageLocalInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Files\Resources\Messenger\Request\Images\CDNUploadImageMessage;
use Doctrine\ORM\Mapping\Table;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'baks:drom-products:cdn', description: 'Комманда отправляет на CDN файлы изображений')]
final class DromProductWebpImageCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $upload,
        private readonly MessageDispatchInterface $messageDispatch,
        private readonly DromProductImageLocalInterface $DromProductImageLocalRepository,
        private readonly DromImageIdentifierByNameInterface $DromImageIdentifierByNameRepository
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
        $images = $this->DromProductImageLocalRepository->findAll();

        foreach($images as $image)
        {
            $message = new CDNUploadImageMessage(
                $image->getId(),
                DromProductImage::class,
                $image->getName(),
            );

            $this->messageDispatch->dispatch($message);
            $progressBar->advance();
        }

        $this->DromImageIdentifierByNameRepository->find('MVvHfrUQU');


        /** Проверяем директории на признак не пережатых файлов */

        /** Выделяем из сущности название таблицы для директории файлов */
        $ref = new ReflectionClass(DromProductImage::class);

        /** @var ReflectionAttribute $current */
        $current = current($ref->getAttributes(Table::class));
        $TABLE = $current->getArguments()['name'] ?? 'images';

        /** Определяем путь к директории файлов */
        $upload = null;
        $upload[] = $this->upload;
        $upload[] = 'public';
        $upload[] = 'upload';
        $upload[] = $TABLE;
        $uploadDir = implode(DIRECTORY_SEPARATOR, $upload);

        if(false === is_dir($uploadDir))
        {
            return Command::SUCCESS;
        }

        $iterator = new RecursiveDirectoryIterator($uploadDir, FilesystemIterator::SKIP_DOTS);

        /** @var SplFileInfo $info */
        foreach(new RecursiveIteratorIterator($iterator) as $info)
        {
            /** Определяем файл в базе данных по названию директории */
            $dirName = basename(dirname($info->getRealPath()));
            $dromProductImage = $this->DromImageIdentifierByNameRepository->find($dirName);

            if(false === $dromProductImage)
            {
                $io->warning(sprintf(
                    'Изображение DromProductImage %s не найдено либо уже отправлено на CDN',
                    $dirName
                ));

                unlink($info->getRealPath()); // удаляем файл
                rmdir($info->getPath());  // удаляем пустую директорию

                continue;
            }

            $CDNUploadImageMessage = new CDNUploadImageMessage(
                $dromProductImage,
                DromProductImage::class,
                $dirName,

            );

            $this->messageDispatch->dispatch(message: $CDNUploadImageMessage);

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->success('Команда успешно завершена');

        return Command::SUCCESS;
    }
}
