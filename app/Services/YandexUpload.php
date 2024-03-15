<?php
namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class YandexUpload
{
    protected $token = 'y0_AgAAAAA_evKYAAtedAAAAAD8iqqsAADT3uZawARDDJ1daN3ibxmrKHUkWA';
    public function execute($file, string $type, string $directory = '')
    {
        $client = new Client();
        // Создаем директорию
        $status = $this->createDirectory($directory, $client);

        if ($status === 200 || $status === 201) {
            try {
                // Получаем путь к загружаемому файлу
                $filePath = Storage::path($file);
                $name     =  pathinfo($filePath, PATHINFO_BASENAME);

                // Генерируем уникальное имя файла
                $fileName = uniqid() . '_' . $name;

                // URL для загрузки файла на Яндекс.Диск
                $uploadUrl = 'https://cloud-api.yandex.net/v1/disk/resources/upload?path=' . $directory . '/' . $fileName;

                // Отправляем запрос на загрузку файла на Яндекс.Диск
                $response = $client->request('GET', $uploadUrl, [
                    'headers' => [
                        'Authorization' => 'OAuth ' . $this->token,
                    ],
                ]);

                // Получаем URL для загрузки файла
                $uploadUrl = json_decode($response->getBody())->href;

                // Загружаем файл на полученный URL
                $response = $client->request('PUT', $uploadUrl, [
                    'headers' => [
                        'Content-Type' => 'application/octet-stream',
                    ],
                    'body' => fopen($filePath, 'r'),
                ]);

                // Публикуем файл
                $response = $client->request(
                    'PUT',
                    'https://cloud-api.yandex.net/v1/disk/resources/publish?path=' . $directory . '/' . $fileName, [
                    'headers' => [
                        'Authorization' => 'OAuth ' . $this->token,
                    ],
                ]);

                // Получаем ответ от сервера
                $statusCode = $response->getStatusCode();

                // Если файл успешно загружен
                if ($statusCode === 201 || $statusCode === 200) {
                    // публикуем файл
                    $response = $client->request('GET', 'https://cloud-api.yandex.net/v1/disk/resources/public', [
                        'headers' => [
                            'Authorization' => $this->token
                        ],
                        'query' => [
                            'path' => '/'.$directory . '/' . $fileName // Путь к файлу на Яндекс.Диске
                        ]
                    ]);

                    $body      = json_decode($response->getBody()->getContents(), true);
                    $publicUrl = "https://getfile.dokpub.com/yandex/get/". $body['items'][array_key_last($body['items'])]['public_url']; // Получение публичной ссылки на файл

                    // Возвращаем URL загруженного файла вместе с сообщением об успехе
                    return [
                        'file_url' => $publicUrl
                    ];
                } else {
                    // В случае ошибки возвращаем сообщение об ошибке
                    return response()->json(['error' => 'Failed to upload file'], $statusCode);
                }
            } catch (RequestException $e) {
                // Если произошла ошибка запроса, возвращаем сообщение об ошибке
                return response()->json(['error' => $e->getMessage()], $e->getCode());
            }
        }
    }

    protected function createDirectory(string $directory, Client $client): int
    {
        try {
            $response = $client->request('GET', 'https://cloud-api.yandex.net/v1/disk/resources?path=' . $directory, [
                'headers' => [
                    'Authorization' => 'OAuth ' . $this->token,
                ],
            ]);

            // Получаем код состояния ответа
            return $response->getStatusCode();
        } catch (RequestException $e) {
            if ($e->getCode() === 404) {
                $response = $client->request(
                    'PUT',
                    'https://cloud-api.yandex.net/v1/disk/resources?path=' . $directory, [
                    'headers' => [
                        'Authorization' => 'OAuth ' . $this->token,
                    ],
                ]);

                // Проверяем успешность создания директории
                $statusCode = $response->getStatusCode();

                if ($statusCode != 201) {
                    return $statusCode;
                }

                return $response->getStatusCode();
            }

            return $e->getCode();
        }
    }
}
