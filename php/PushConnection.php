<?php

/**
 * Biblioteka implementująca BotAPI GG <https://boty.gg.pl>
 * Wymagane: PHP 5.6+, php-cURL.
 *
 * Copyright (C) 2013-2016 Xevin Consulting Limited Marcin Bagiński <marcin.baginski@firma.gg.pl>
 * Modified by KsaR 2016-2017 <https://github.com/KsaR99/>
 *
 * This library is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 */

spl_autoload_register(function($class) {
    require_once __DIR__."/{$class}.php";
});

/**
 * @brief Klasa reprezentująca połączenie PUSH z BotMasterem.
 */
class PushConnection
{
    /**
     * Obiekt autoryzacji
     *
     * Typ BotAPIAuthorization
     */
    public static $authorization = [];
    private static $authorizationData = [];
    private static $lastBotGGNumber = null;

    public static $BOTAPI_LOGIN = null;
    public static $BOTAPI_PASSWORD = null;

    private $botGGNumber;

    /**
     * Statusy GG
     */
    const STATUS_AWAY = 'away';
    const STATUS_FFC = 'ffc';
    const STATUS_BACK = 'back';
    const STATUS_DND = 'dnd';
    const STATUS_INVISIBLE = 'invisible';

    /**
     * Curl debug
     * domyślnie: false
     */
    const CURL_VERBOSE = false;

    /**
     * Konstruktor PushConnection - inicjuje dane autoryzacji
     *
     * @param int $gg Numer GG bota
     * @param string $email Login
     * @param string $pass Hasło
     */
    public function __construct($botGGNumber = null, $email = null, $pass = null)
    {
        if ($botGGNumber === null) {
            if (self::$lastBotGGNumber !== null) {
                $botGGNumber = self::$lastBotGGNumber;
            } else {
                throw new PushConnectionException('Nie podano numeru gg bota.');
            }
        } else {
            self::$lastBotGGNumber = $botGGNumber;
        }

        $this->botGGNumber = $botGGNumber;

        if (!isset(self::$authorizationData[$botGGNumber])
            || ($email !== null && $email !== self::$authorizationData[$botGGNumber]['email'])
            || ($pass !== null && $pass !== self::$authorizationData[$botGGNumber]['pass'])) {

            self::$authorizationData[$botGGNumber] = [
                'email' => ($email === null) ? self::$BOTAPI_LOGIN : $email,
                'pass' => ($pass === null) ? self::$BOTAPI_PASSWORD : $pass
            ];

            self::$authorization[$botGGNumber] = null;
        }
    }

    /**
     * Autoryzuje bota.
     */
    public function authorize()
    {
        if (self::$authorization[$this->botGGNumber] !== null
            && self::$authorization[$this->botGGNumber]->isAuthorized()) {

            return true;
        }

        self::$authorization[$this->botGGNumber] = new BotAPIAuthorization(
            $this->botGGNumber,
            self::$authorizationData[$this->botGGNumber]['email'],
            self::$authorizationData[$this->botGGNumber]['pass']
        );

        return self::$authorization[$this->botGGNumber]->isAuthorized();
    }

    /**
     * Wysyła wiadomość (obiekt lub tablicę obiektów MessageBuilder) do BotMastera.
     *
     * @param array|MessageBuilder $message Obiekt lub tablica obiektów MessageBuilder
     */
    public function push($messages)
    {
        if (!$this->authorize()) {
            return false;
        }

        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $data = self::$authorization[$this->botGGNumber]->getServerAndToken();

        foreach ($messages as $message) {
            $result = $this->executeCurl([
                CURLOPT_URL => 'https://'.$data['server'].'/sendMessage/'.$this->botGGNumber,
                CURLOPT_POSTFIELDS => 'msg='.urlencode($message->getProtocolMessage())
                                    . '&to='.implode(',', $message->recipientNumbers),
                CURLOPT_HTTPHEADER => [
                    'BotApi-Version: '.MessageBuilder::BOTAPI_VERSION,
                    'Token: '.$data['token']
            ]]);

            if (strpos($result, '<status>0</status>') === false) {
                throw new UnableToSendMessageException('Nie udało się wysłać wiadomości.');
            }
        }

        return true;
    }

    /**
     * Ustawia opis botowi.
     *
     * @param string $description Treść opisu
     * @param string $status Typ opisu
     */
    public function setStatus($description, $status = '')
    {
        if (!$this->authorize()) {
            return false;
        }

        switch ($status) {
            case self::STATUS_AWAY: $h = empty($description) ? 3 : 5; break;
            case self::STATUS_FFC: $h = empty($description) ? 23 : 24; break;
            case self::STATUS_BACK: $h = empty($description) ? 2 : 4; break;
            case self::STATUS_DND: $h = empty($description) ? 33 : 34; break;
            case self::STATUS_INVISIBLE: $h = empty($description) ? 20 : 22; break;
            default: $h = 0;
        }

        $data = self::$authorization[$this->botGGNumber]->getServerAndToken();
        $result = $this->executeCurl([
            CURLOPT_URL => 'https://'.$data['server'].'/setStatus/'.$this->botGGNumber,
            CURLOPT_POSTFIELDS => "status=$h&desc=".urlencode($description),
            CURLOPT_HTTPHEADER => [
                'BotApi-Version: '.MessageBuilder::BOTAPI_VERSION,
                'Token: '.$data['token']
            ]
        ]);

        if (strpos($result, '<status>0</status>') === false) {
            throw new UnableToSetStatusExteption('Niepowodzenie ustawiania statusu.');
        }

        return true;
    }

    /**
     * Wykonuje żądania cURL
     * @param array $opt Opcje cURL
     *
     * @return string
     */
    private function executeCurl($opt)
    {
        $ch = curl_init();

        curl_setopt_array($ch, $opt + [
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_VERBOSE => self::CURL_VERBOSE
        ]);

        $result = curl_exec($ch);
        $http_res = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_res === 401) {
            // token expired
            self::$authorization[$this->botGGNumber] = null;
            throw new TokenExpiredException('Token wygasł. Należy ponowić żądanie.');
        }

        curl_close($ch);

        return $result;
    }

    /**
     * Tworzy i zwraca uchwyt do nowego żądania cURL
     */
    private function imageCurl($type, $data)
    {
        return $this->executeCurl([
            CURLOPT_URL => "https://botapi.gadu-gadu.pl/botmaster/{$type}Image/".$this->botGGNumber,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'BotApi-Version: '.MessageBuilder::BOTAPI_VERSION,
                'Token: '.self::$authorization[$this->botGGNumber]->getServerAndToken()['token'],
                'Expect: '
            ]
        ]);
    }

    /**
     * Wysyła obrazek do Botmastera
     */
    public function putImage($data)
    {
        return ($this->authorize())
            ? strpos($this->imageCurl('put', $data), '<status>0</status>') !== false
            : false;
    }

    /**
     * Pobiera obrazek z Botmastera
     */
    public function getImage($hash)
    {
        return ($this->authorize())
            ? explode("\r\n\r\n", $this->imageCurl('get', 'hash='.$hash), 2)[1]
            : false;
    }

    /**
     * Sprawdza, czy Botmaster ma obrazek
     */
    public function existsImage($hash)
    {
        return ($this->authorize())
            ? strpos($this->imageCurl('exists', 'hash='.$hash), '<status>0</status>') !== false
            : false;
    }

    /**
     * Sprawdza, czy numer jest botem
     */
    public function isBot($ggid)
    {
        if (!$this->authorize()) {
            return false;
        }

        $result = $this->executeCurl([
            CURLOPT_URL => 'https://botapi.gadu-gadu.pl/botmaster/isBot/'.$this->botGGNumber,
            CURLOPT_POSTFIELDS => 'check_ggid='.$ggid,
            CURLOPT_HTTPHEADER => [
                'BotApi-Version: '.MessageBuilder::BOTAPI_VERSION,
                'Token: '.self::$authorization[$this->botGGNumber]->getServerAndToken()['token']
            ]
        ]);

        return strpos($result, '<status>1</status>') !== false;
    }
}
