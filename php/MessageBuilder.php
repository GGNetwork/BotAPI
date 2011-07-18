<?php
/*******
Biblioteka implementująca BotAPI GG http://boty.gg.pl/
Copyright (C) 2011 GG Network S.A. Marcin Bagiński <m.baginski@gadu-gadu.pl>

This library is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program. If not, see<http://www.gnu.org/licenses/>.
*******/

define ('FORMAT_NONE', 0x00);
define ('FORMAT_BOLD_TEXT', 0x01);
define ('FORMAT_ITALIC_TEXT', 0x02);
define ('FORMAT_UNDERLINE_TEXT', 0x04);
define ('FORMAT_NEW_LINE', 0x08);

/**
 * @brief Reprezentacja wiadomości
 */
class MessageBuilder
{
	/**
	 * Tablica numerów GG do których ma trafić wiadomość
	 *
	 * array
	 */
	public $recipientNumbers=array();

	/**
	 * Określa czy wiadomość zostanie wysłana do numerów będących offline, domyślnie true
	 *
	 * bool
	 */
	public $sendToOffline=true;

	public $html='';
	public $text='';
	public $format='';
	public $img=NULL;

	public $R=0;
	public $G=0;
	public $B=0;

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
		$this->recipientNumbers=array();

		$this->sendToOffline=true;

		$this->html='';
		$this->text='';
		$this->format='';
		$this->img=NULL;

		$this->R=0;
		$this->G=0;
		$this->B=0;
	}

	/**
	 * Dodaje tekst do wiadomości
	 *
	 * @param string $text tekst do wysłania
	 * @param int $formatBits styl wiadomości (FORMAT_BOLD_TEXT, FORMAT_ITALIC_TEXT, FORMAT_UNDERLINE_TEXT), domyślnie brak
	 * @param int $R, $G, $B składowe koloru tekstu w formacie RGB
	 *
	 * @return MessageBuilder this
	 */
	public function addText($text, $formatBits=FORMAT_NONE, $R=0, $G=0, $B=0)
	{
		if ($formatBits & FORMAT_NEW_LINE)
			$text.="\r\n";

		$text=str_replace("\r\r", "\r", str_replace("\n", "\r\n", $text));
		$html=str_replace("\r\n", '<br>', htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8'));


		if ($this->format!==NULL) {
			$this->format.=pack(
					'vC',
					mb_strlen($this->text, 'UTF-8'),
					(($formatBits & FORMAT_BOLD_TEXT)) |
						(($formatBits & FORMAT_ITALIC_TEXT)) |
						(($formatBits & FORMAT_UNDERLINE_TEXT)) |
						((1 || $R!=$this->R || $G!=$this->G || $B!=$this->B) * 0x08)
				);
			#if ($R!=$this->R || $G!=$this->G || $B!=$this->B) {
				$this->format.=pack('CCC', $R, $G, $B);

				$this->R=$R;
				$this->G=$G;
				$this->B=$B;
			#}

			$this->text.=$text;
		}


		if ($R || $G || $B) $html='<span style="color:#'.str_pad(dechex($R), 2, '0', STR_PAD_LEFT).str_pad(dechex($G), 2, '0', STR_PAD_LEFT).str_pad(dechex($B), 2, '0', STR_PAD_LEFT).';">'.$html.'</span>';
		if ($formatBits & FORMAT_BOLD_TEXT) $html='<b>'.$html.'</b>';
		if ($formatBits & FORMAT_ITALIC_TEXT) $html='<i>'.$html.'</i>';
		if ($formatBits & FORMAT_UNDERLINE_TEXT) $html='<u>'.$html.'</u>';

		$this->html.=$html;


		return $this;
	}

	/**
	 * Dodaje tekst do wiadomości
	 *
	 * @param string $text tekst do wysłania w formacie BBCode
	 *
	 * @return MessageBuilder this
	 */
	public function addBBcode($bbcode)
	{
		$tagsLength=0;
		$heap=array();
		$start=0;
		$bbcode=str_replace('[br]', "\n", $bbcode);

		while (preg_match('/\[(\/)?(b|i|u|color)(=#?[0-9a-f]{6})?\]/', $bbcode, $out, PREG_OFFSET_CAPTURE, $start)) {
			$s=substr($bbcode, $start, $out[0][1]-$start);
			$c=array(0, 0, 0);
			if (strlen($s)) {
				$flags=0;
				$c=array(0, 0, 0);
				foreach ($heap as $h) {
					switch ($h[0]) {
						case 'b': { $flags|=0x01; break; }
						case 'i': { $flags|=0x02; break; }
						case 'u': { $flags|=0x04; break; }
						case 'color': { $c=$h[1]; break; }
					}
				}


				$this->addText($s, $flags, $c[0], $c[1], $c[2]);
			}


			$start=$out[0][1]+strlen($out[0][0]);


			if ($out[1][0]=='') {
				switch ($out[2][0]) {
					case 'b':
					case 'i':
					case 'u': {
							array_push($heap, array($out[2][0]));
							break;
						}

					case 'color': {
							$c=hexdec(substr($out[3][0], -6, 6));
							$c=array(
									($c >> 16) & 0xFF,
									($c >> 8) & 0xFF,
									($c >> 0) & 0xFF
								);

							array_push($heap, array('color', $c));
							break;
						}
				}

				$tagsLength+=strlen($out[0][0]);


			} else {
				array_pop($heap);
				$tagsLength+=strlen($out[0][0]);
			}
		}


		$s=substr($bbcode, $start);
		if (strlen($s))
			$this->addText($s);


		return $this;
	}

	/**
	 * Dodaje tekst do wiadomości
	 *
	 * @param string $text tekst do wysłania w HTMLu
	 *
	 * @return MessageBuilder this
	 */
	public function addRawHtml($html)
	{
		$this->html.=$html;
		return $this;
	}

	/**
	 * Ustawia tekst do wiadomości
	 *
	 * @param string $html tekst do wysłania w HTMLu
	 *
	 * @return MessageBuilder this
	 */
	public function setRawHtml($html)
	{
		$this->html=$html;
		return $this;
	}

	/**
	 * Ustawia tekst wiadomości alternatywnej
	 *
	 * @param string $text tekst do wysłania dla GG7.7 i starszych
	 *
	 * @return MessageBuilder this
	 */
	public function setAlternativeText($message)
	{
		$this->format=NULL;
		$this->text=$message;
		return $this;
	}

	/**
	 * Dodaje obraz do wiadomości
	 *
	 * @param string $fileName nazwa pliku w formacie JPEG
	 *
	 * @return MessageBuilder this
	 */
	public function addImage($fileName, $isFile=true)
	{
		if ($isFile) {
			$this->img=file_get_contents($fileName);

		} else
			$this->img=$fileName;


		$this->imgCrc=crc32($this->img);


		$this->format.=pack('vCCCVV', strlen($this->text), 0x80, 0x09, 0x01, strlen($this->img), $this->imgCrc);

		$this->addRawHtml('<img name="'.sprintf('%08x%08x', $this->imgCrc, strlen($this->img)).'">');
		return $this;
	}

	/**
	 * Ustawia odbiorców wiadomości
	 *
	 * @param int|string|array recipientNumbers numer GG adresata (lub tablica)
	 *
	 * @return MessageBuilder this
	 */
	public function setRecipients($recipientNumbers)
	{
		$this->recipientNumbers=(array) $recipientNumbers;
		return $this;
	}

	/**
	 * Zawsze dostarcza wiadomość
	 *
	 * @return MessageBuilder this
	 */
	public function setSendToOffline($sendToOffline)
	{
		$this->sendToOffline=$sendToOffline;
		return $this;
	}

	/**
	 * Tworzy sformatowaną wiadomość do wysłania do BotMastera
	 */
	public function getProtocolMessage($includeImage=false)
	{
		if (preg_match('/^<span[^>]*>.+<\/span>$/s', $this->html, $o)) {
			if ($o[0]!=$this->html)
				$this->html='<span style="color:#000000; font-family:\'MS Shell Dlg 2\'; font-size:9pt; ">'.$this->html.'</span>';

		} else
			$this->html='<span style="color:#000000; font-family:\'MS Shell Dlg 2\'; font-size:9pt; ">'.$this->html.'</span>';


		$s=pack('VVVV', strlen($this->html)+1, strlen($this->text)+1, (($this->img) ? 16+(($includeImage) ? strlen($this->img) : 0) : 0), ((empty($this->format)) ? 0 : strlen($this->format)+3)).$this->html."\0".$this->text."\0".(($this->img!==NULL) ? sprintf('%08x%08x', $this->imgCrc, strlen($this->img)).(($includeImage) ? $this->img : ''): '').((empty($this->format)) ? '' : pack('Cv', 0x02, strlen($this->format)).$this->format);

		return $s;
	}

	/**
	 * Zwraca na wyjście sformatowaną wiadomość do wysłania do BotMastera
	 */
	public function reply()
	{
		if (sizeof($this->recipientNumbers))
			header('To: '.join(',', $this->recipientNumbers));
		if (!$this->sendToOffline)
			header('Send-to-offline: 0');

		echo $this->getProtocolMessage(true);
	}
}
