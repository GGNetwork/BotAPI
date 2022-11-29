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
 * @brief Reprezentacja wiadomości
 */
class MessageBuilder
{
    /**
     * Lista odbiorców ( numery GG )
     */
    public $recipientNumbers = [];

    private $html = '';
    private $text = '';
    private $format = null;

    const BOTAPI_VERSION = 'GGBotAPI-3.1;PHP-'.PHP_VERSION;
    const IMG_FILE = true;
    const IMG_RAW = false;

    /**
     * Konstruktor MessageBuilder
     */
    public function __construct()
    {
    }

    /**
     * Czyści całą wiadomość
     */
    public function clear()
    {
        $this->recipientNumbers = [];

        $this->html = '';
        $this->text = '';
        $this->format = null;
    }

    /**
     * Dodaje tekst do wiadomości
     *
     * @param string $text Tekst do wysłania
     *
     * @return MessageBuilder this
     */
    public function addText($text)
    {
        $text = str_replace(["\n", "\r\r"], ["\r\n", "\r"], $text);
        $html = str_replace("\r\n", '<br>', htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8'));

        if ($this->format !== null) {
            $this->text .= $text;
        }

        $this->html .= $html;

        return $this;
    }

    /**
     * Dodaje tekst do wiadomości
     *
     * @param string $html Tekst do wysłania w HTMLu
     *
     * @return MessageBuilder this
     */
    public function addRawHtml($html)
    {
        $this->html .= $html;

        return $this;
    }

    /**
     * Ustawia tekst do wiadomości
     *
     * @param string $html Tekst do wysłania w HTMLu
     *
     * @return MessageBuilder this
     */
    public function setRawHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Ustawia tekst wiadomości alternatywnej
     *
     * @param string $message Tekst do wysłania dla GG 7.7 i starszych
     *
     * @return MessageBuilder this
     */
    public function setAlternativeText($message)
    {
        $this->format = null;
        $this->text = $message;

        return $this;
    }

    /**
     * Dodaje obraz do wiadomości
     *
     * @param string $pathOrContent Ścieżka do obrazka lub dane obrazka
     * @param bool $isPath Wskazuje co zawiera zmienna pathOrContent
     * 
     * @return MessageBuilder this
     */
    public function addImage($pathOrContent, $isPath = self::IMG_FILE)
    {
        $p = new PushConnection;
        if (!$p->authorize()) {
            throw new MessageBuilderException("'PushConnection' nie jest zautoryzowany");
        }

        if ($isPath) {
            $content = file_get_contents($pathOrContent);
        } else {
            $content =& $pathOrContent;
        }

        $crc = crc32($content);
        $length = strlen($content);
        $hash = sprintf('%08x%08x', $crc, $length);

        if (!$p->existsImage($hash) && !$p->putImage($content)) {
            throw new UnableToSendImageException('Nie udało się wysłać obrazka');
        }

        $this
            ->addRawHtml('<img name="'.$hash.'">')
            ->format .= pack('vCCCVV', strlen($this->text), 0x80, 0x09, 0x01, $length, $crc);

        return $this;
    }

    /**
     * Ustawia odbiorców wiadomości
     *
     * @param int|string|array $recipientNumbers GG odbiorców
     *
     * @return MessageBuilder this
     */
    public function setRecipients($recipientNumbers)
    {
        $this->recipientNumbers = (array)$recipientNumbers;

        return $this;
    }

    /**
     * Tworzy sformatowaną wiadomość do wysłania do BotMastera
     */
    public function getProtocolMessage()
    {
        $formatLen = strlen($this->format);

        return pack('VVVV',
                    strlen($this->html)+1,
                    strlen($this->text)+1,
                    0,
                    ($formatLen ? $formatLen+3 : 0))
            .  $this->html."\0$this->text\0"
            . ($formatLen ? pack('Cv', 0x02, $formatLen).$this->format : '');
    }

    /**
     * Zwraca sformatowaną wiadomość do wysłania do BotMastera
     */
    public function reply()
    {
        if (!empty($this->recipientNumbers)) {
            header('To: '.implode(',', $this->recipientNumbers));
        }

        header('BotApi-Version: '.self::BOTAPI_VERSION);

        echo $this->getProtocolMessage();
    }
}
