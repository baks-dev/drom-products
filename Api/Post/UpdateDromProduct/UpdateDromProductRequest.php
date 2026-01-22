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

namespace BaksDev\Drom\Products\Api\Post\UpdateDromProduct;

use InvalidArgumentException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;

final class UpdateDromProductRequest
{
    const string BASE_URL = 'https://baza.drom.ru/';

    /** Id прайс-листа */
    private ?string $priceListId = null;

    /** Ключ для авторизации */
    private ?string $auth = null;

    public function setPriceListId(string $priceListId): self
    {
        $this->priceListId = $priceListId;
        return $this;
    }

    public function setAuth(string $auth): self
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Запрос на обновление данных прайс-листа (добавление, изменение или удаление). Метод принимает в качестве
     * аргумента $xml строку в XML-формате с данными для изменения/добавления в прайс-лист.
     *
     * Обязательные параметры запроса:
     *
     * - packetId - id прайс-листа, в котором нужно обновить товар. Значение вида 55359 хранится в ссылке на прайс-лист
     * (https://baza.drom.ru/personal/goods/packet/{id}/recurrent-update)
     * - auth - Должен вычисляться как hash('sha512', X), где X - строка с ключом. Ключ уникален на кабинет,
     * предоставляется по запросу. Чтобы получить auth, необходимо рассчитать хэш по алгоритму sha512 от строки с ключом.
     * - data - Файл или бинарный контент, данные товаров в том же формате, в котором был загружен изменяемый прайс-лист.
     * Размер данных, переданных в этом параметре, не должен превышать 5 МБ.
     *
     *
     * Требования к данным:
     *
     * - Файл с изменениями, имеет такой же формат, как и исходный загруженный прайс. Если в исходном прайсе первая
     * строка - это заголовок таблицы, то с нее же должны начинаться и все файлы. Смена формата повлечет за собой ошибку
     * при выполнении запроса.
     * - Допустимые форматы: XLS, CSV, XML. При смене форматов с XLS на CSV, обновление пройдет успешно.
     * - В минимальном варианте XML-прайс содержит информацию о наименовании, описании, количестве и цене товара.
     * - Инструкция по составлению XML-прайса: https://baza.drom.ru/help/XmlPriceCreate1
     * - Указана колонка, отвечающая за количество. Если товар нужно удалить, в колонке отправить значение "0".
     *
     * https://baza.drom.ru/help/API
     */
    public function post(string $xml): bool
    {
        /** Не пытаемся отправить данные, если не был указан идентификатор прайс-листа или ключ для авторизации */
        if(empty($this->priceListId))
        {
            throw new InvalidArgumentException('Не передан обязательный параметр PriceListId');
        }

        if(empty($this->auth))
        {
            throw new InvalidArgumentException('Не передан обязательный параметр Auth');
        }

        $headers['Content-Type'] = 'multipart/form-data';

        $request = new RetryableHttpClient(
            HttpClient::create(['headers' => $headers])
                ->withOptions([
                    'base_uri' => 'https://oauth2.googleapis.com',
                    'verify_host' => false,
                ])
        );

        $data = [
            'packetId' => $this->priceListId,
            'auth' => hash('sha512', $this->auth),
            'xml' => $xml,
        ];

        $response = $request->request('POST', '/good/packet/api/sync', ["body" => $data]);

        if($response->getStatusCode() !== 200)
        {
            return false;
        }

        return true;
    }
}
