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

namespace BaksDev\Drom\Products\Entity;

use BaksDev\Core\Entity\EntityState;
use BaksDev\Drom\Products\Entity\Images\DromProductImage;
use BaksDev\Drom\Products\Entity\Kit\DromProductKit;
use BaksDev\Drom\Products\Entity\Profile\DromProductProfile;
use BaksDev\Drom\Products\Type\Id\DromProductUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'drom_product')]
class DromProduct extends EntityState
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: DromProductUid::TYPE)]
    private DromProductUid $id;

    /** ID продукта (не уникальное) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductUid::TYPE)]
    private ProductUid $product;

    /** Константа ТП */
    #[ORM\Column(type: ProductOfferConst::TYPE, nullable: true)]
    private ?ProductOfferConst $offer = null;

    /** Константа множественного варианта */
    #[ORM\Column(type: ProductVariationConst::TYPE, nullable: true)]
    private ?ProductVariationConst $variation = null;

    /** Константа модификации множественного варианта */
    #[ORM\Column(type: ProductModificationConst::TYPE, nullable: true)]
    private ?ProductModificationConst $modification = null;

    /** Коллекция "живых" изображений продукта */
    #[ORM\OrderBy(['root' => 'DESC'])]
    #[ORM\OneToMany(targetEntity: DromProductImage::class, mappedBy: 'drom', cascade: ['all'], fetch: 'EAGER')]
    private Collection $images;


    /** Шаблон описания */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Идентификатор профиля */
    #[ORM\OneToOne(targetEntity: DromProductProfile::class, mappedBy: 'drom', cascade: ['all'])]
    private DromProductProfile $profile;

    /** Комплекты */
    #[ORM\OneToOne(targetEntity: DromProductKit::class, mappedBy: 'drom', cascade: ['all'])]
    private DromProductKit $kit;


    public function __construct()
    {
        $this->id = new DromProductUid();
        $this->images = new ArrayCollection();
        $this->profile = new DromProductProfile($this);
        $this->kit = new DromProductKit($this);
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): DromProductUid
    {
        return $this->id;
    }

    /** Гидрирует переданную DTO, вызывая ее сеттеры */
    public function getDto($dto): mixed
    {
        if($dto instanceof DromProductInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /** Гидрирует сущность переданной DTO */
    public function setEntity($dto): mixed
    {
        if($dto instanceof DromProductInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /** Метод возвращает коллекцию изображений для сжатия в формат WEBP */
    public function getImages(): Collection
    {
        return $this->images;
    }
}
