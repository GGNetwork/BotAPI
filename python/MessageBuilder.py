#!/usr/bin/env python
# -*- coding: utf-8 -*-

'''
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
'''

import re
import cgi
import struct
import zlib
import string


FORMAT_NONE=0
FORMAT_BOLD_TEXT=1
FORMAT_ITALIC_TEXT=2
FORMAT_UNDERLINE_TEXT=4
FORMAT_NEW_LINE=8


class MessageBuilder:
	RE_PROTOCOL_MESSAGE=re.compile('^<span[^>]*>.+<\/span>$')


	'''Reprezentacja wiadomości'''
	def __init__(self):
		self.clear()


	def clear(self):
		'''Czyści całą wiadomość'''
		self.recipientNumbers=[]

		self.sendToOffline=True

		self.html=''
		self.text=''
		self.format=''
		self.img=None

		self.R=0
		self.G=0
		self.B=0


	def addText(self, text, formatBits=FORMAT_NONE, R=0, G=0, B=0):
		'''Dodaje tekst do wiadomości'''
		if formatBits & FORMAT_NEW_LINE:
			text=text+"\r\n"

		text=string.replace(string.replace(text, "\n", "\r\n"), "\r\r", "\r")
		html=string.replace(cgi.escape(text), "\r\n", '<br>')

		self.format=self.format+struct.pack('<HBBBB', len(self.text.decode('UTF-8')), (formatBits & FORMAT_BOLD_TEXT) | (formatBits & FORMAT_ITALIC_TEXT) | (formatBits & FORMAT_UNDERLINE_TEXT) | 0x08, R, G, B)

		self.R=R
		self.G=G
		self.B=B

		self.text=self.text+text



		if R or G or B: html='<span style="color:#%s%s%s;">%s</span>' % (("%x" % R).rjust(2, '0'), ("%x" % G).rjust(2, '0'), ("%x" % B).rjust(2, '0'), html)
		if formatBits & FORMAT_BOLD_TEXT:
			html='<b>%s</b>' % html
		if formatBits & FORMAT_ITALIC_TEXT:
			html='<i>%s</i>' % html
		if formatBits & FORMAT_UNDERLINE_TEXT:
			html='<u>%s</u>' % html

		self.html=self.html+html


	def addBBcode(self, bbcode):
		'''Dodaje tekst do wiadomości'''
		tagsLength=0
		heap=[]
		start=0
		bbcode=string.replace(bbcode, '[br]', "\n")


		out=re.search(r'\[(/?)(b|i|u|color)(=#?([0-9abcdef]{6}))?\]', bbcode)
		while out is not None:
			s=bbcode[:out.start(0)]
			c=[0,0,0]

			if len(s)>0:
				flags=0
				c=[0,0,0]

				for h in heap:
					if h[0]=='b':
						flags=flags|FORMAT_BOLD_TEXT
					elif h[0]=='i':
						flags=flags|FORMAT_ITALIC_TEXT
					elif h[0]=='u':
						flags=flags|FORMAT_UNDERLINE_TEXT
					elif h[0]=='color':
						c=h[1]

				self.addText(s, flags, c[0], c[1], c[2])


			if out.group(1)[0:1]!='/':
				if out.group(2)=='color':
					c=out.group(4)
					c=[int(c[0:2], 16), int(c[2:4], 16), int(c[4:6], 16)]
					heap.append(['color',c])

				else:
					heap.append([out.group(2)])

			else:
				heap.pop()


			tagsLength=tagsLength+len(out.group(0))

			bbcode=bbcode[out.end():]
			out=re.search(r'\[(/?)(b|i|u|color)(=#?([0-9abcdef]{6}))?\]', bbcode)


		if len(bbcode)>0:
			self.addText(bbcode)


	def addRawHtml(self, html):
		'''Dodaje tekst do wiadomości'''
		self.html=self.html+html


	def setRawHtml(self, html):
		'''Ustawia tekst wiadomości'''
		self.html=html


	def setAlternativeText(self, message):
		'''Ustawia tekst wiadomości alternatywnej'''
		self.format=struct.pack('<HB', 0, 0)
		self.text=message


	def addImage(self, fileName, isFile=True):
		'''Dodaje obraz do wiadomości'''
		if isFile:
			self.img=open(fileName, 'rb').read()
		else:
			self.img=fileName

		self.imgCrc=zlib.crc32(self.img) % 2**32
		self.format=self.format+struct.pack('<HBBBII', len(self.text), 0x80, 0x09, 0x01, len(self.img), self.imgCrc)
		self.addRawHtml('<img name="%08x%08x">' % (self.imgCrc, len(self.img)))
		self.img='%08x%08x' % (self.imgCrc, len(self.img))+self.img


	def setRecipients(self, recipientNumbers):
		'''Ustawia odbiorców wiadomości.

		recipientNumbers to lista numerów GG'''
		self.recipientNumbers=recipientNumbers


	def setSendToOffline(self, sendToOffline):
		'''Zawsze dostarcza wiadomość'''
		self.sendToOffline=sendToOffline


	def getProtocolMessage(self, includeImage=False):
		'''Tworzy sformatowaną wiadomość do wysłania do BotMastera'''
		if re.search(MessageBuilder.RE_PROTOCOL_MESSAGE, self.html) is None:
			self.html='<span style="color:#000000; font-family:\'MS Shell Dlg 2\'; font-size:9pt; ">'+self.html+'</span>'

		imgLen=0
		if self.img is not None and len(self.img)>0:
			imgLen=len(self.img)
		else:
			self.img=''

		formatLen=0
		format=''
		if len(self.format)>0:
			format=struct.pack('<BH', 0x02, len(self.format))+self.format
			formatLen=len(format)

		bin='%s%s\0%s\0%s%s' % (struct.pack('<IIII', len(self.html)+1, len(self.text)+1, imgLen, formatLen), self.html, self.text, self.img, format)

		return bin


	def reply(self, request):
		'''Zwraca na wyjście sformatowaną wiadomość do wysłania do BotMastera'''
		if request is not None:
			if len(self.recipientNumbers)>0:
				request.response.headers.add_header('To', ','.join(map(str, self.recipientNumbers)))

			if self.sendToOffline==False:
				request.response.headers.add_header('Send-to-offline', '0')

		return self.getProtocolMessage(True)
