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
import urllib2
import base64
import urllib
from django.utils import simplejson as json
from MessageBuilder import *
from xml.etree import ElementTree


STATUS_AWAY='away'
STATUS_FFC='ffc'
STATUS_BACK='back'
STATUS_DND='dnd'
STATUS_INVISIBLE='invisible'


class PushConnection:
	''' Klasa reprezentująca połączenie PUSH z BotMasterem.
	Autoryzuje połączenie w trakcie tworzenia i wysyła wiadomości do BotMastera. '''
	URL_MESSAGE='https://%s/sendMessage/%d'
	URL_STATUS='https://%s/setStatus/%d'
	URL_USERBARS='https://botapi.gadu-gadu.pl/botmaster/getUserbars/%d'


	def __init__(self, ggid, userName, password):
		''' Konstruktor PushConnection - przeprowadza autoryzację

		ggid to numer GG bota.
		userName to login platformy BotAPI.
		password hasło do platformy BotAPI.'''
		self.authorization=BotAPIAuthorization(ggid, userName, password)
		self.gg=ggid


	def __push(self, parmdictb, offline, image):
		data=self.authorization.getServerAndToken()
		request=urllib2.Request(PushConnection.URL_MESSAGE % (data[1], self.gg))
		request.add_header('Token', data[0])
		request.add_header('Send-to-offline', offline)


		postdata=urllib.unquote(parmdictb)
		request.add_data(postdata)

		file=urllib2.urlopen(request)
		xmlData=file.read()

		if re.search(r'<status>0<\/status>', xmlData) is not None:
			return 1
		return 0


	def __pushSingle(self, message, image):
		'''Wysyła wiadomość do BotMastera.'''
		parmdictb=urllib.urlencode({'to': ','.join(map(str, message.recipientNumbers)), 'msg': urllib.quote(message.getProtocolMessage(image))})
		if message.sendToOffline:
			offline='1'
		else:
			offline='0'

		return self.__push(parmdictb, offline, image)


	def __pushMulti(self, message, offline):
		'''Wysyła wiele wiadomości do BotMastera.'''
		parmdictb=urllib.urlencode('&'.join(map(str, messagesMultiOffline)))
		return self.__push(parmdictb, offline, True)


	def push(self, message):
		'''Wysyła listę wiadomości do BotMastera.'''
		if self.authorization.isAuthorized()==False:
			return 0


		count=0

		if isinstance(message, list):
			i=0
			messages=[]
			messagesMulti=[]
			messagesMultiOffline=[]

			for m in message:
				if m.img is not None:
					s=('to%d' % i)+'='+','.join(map(str, m.recipientNumbers))+('&msg%d' % i)+'='+urllib.urlencode(urllib.quote(m.getProtocolMessage()))
					if m.sendToOffline:
						messagesMultiOffline.append(s)

					else:
						messagesMulti.append(s)

					i+=1

				else:
					messages.append(m)


			if len(messagesMulti)>0:
				count+=self.__pushMulti(messagesMulti, '1')

			if len(messagesMultiOffline)>0:
				count+=self.__pushMulti(messagesMulti, '0')

		else:
			messages=[message]


		for message in messages:
			try:
				count+=self.__pushSingle(message, False)


			except urllib2.HTTPError, err:
				if err.code==404:
					count+=self.__pushSingle(message, True)

				else:
					raise

		return count


	def setStatus(self, statusDescription, status='', graphic=False):
		'''Ustawia opis botowi.

		statusDescription treść opisu
		status typ opisu
		graphic flaga czy jest to opis graficzny'''
		try:
			data=self.authorization.getServerAndToken()
			request=urllib2.Request(PushConnection.URL_STATUS % (data[1], self.gg))
			request.add_header('Token', data[0])


			if status==STATUS_AWAY:
				h=3 if len(statusDescription)>0 else 5
			elif status==STATUS_FFC:
				h=23 if len(statusDescription)>0 else 24
			elif status==STATUS_BACK:
				h=2 if len(statusDescription)>0 else 4
			elif status==STATUS_DND:
				h=33 if len(statusDescription)>0 else 34
			elif status==STATUS_INVISIBLE:
				h=20 if len(statusDescription)>0 else 22
			else:
				h=0


			if graphic:
				h=h|256


			parmdictb=urllib.urlencode({'status': h, 'desc': urllib.quote(statusDescription)})
			postdata=urllib.unquote(parmdictb)
			request.add_data(postdata)

			file=urllib2.urlopen(request)
			xmlData=file.read()

			return True

		except urllib2.HTTPError, err:
			return False


	def getUserbars(self):
		'''Pobiera listę kupionych opisów graficznych'''
		data=self.authorization.getServerAndToken()
		request=urllib2.Request(PushConnection.URL_USERBARS % self.gg)
		request.add_header('Token', data[0])
		request.add_header('Accept', 'application/json')

		return json.load(urllib2.urlopen(request))



class BotAPIAuthorization:
	'''Pomocnicza klasa do autoryzacji przez HTTP'''

	URL_AUTH='https://botapi.gadu-gadu.pl/botmaster/getToken/%d'


	def __init__(self, ggid, userName, password):
		self.isValid=self.getData(ggid, userName, password)


	def isAuthorized(self):
		'''Zwraca True jeśli autoryzacja przebiegła prawidłowo'''
		return self.isValid


	def getData(self, ggid, userName, password):
		request=urllib2.Request(BotAPIAuthorization.URL_AUTH % ggid)
		base64string=base64.encodestring('%s:%s' % (userName, password)).replace("\n", "")
		request.add_header("Authorization", "Basic %s" % base64string)

		file=urllib2.urlopen(request)
		xmlData=ElementTree.fromstring(file.read())


		match1=xmlData.findtext(".//token")
		match2=xmlData.findtext(".//server")
		match3=xmlData.findtext(".//port")


		if match1 is None or match2 is None or match3 is None:
			return False

		self.data=[match1, match2, match3]

		return True


	def getServerAndToken(self):
		'''Pobiera aktywny token, port i adres BotMastera'''
		return self.data


	def getToken(self):
		'''Pobiera aktywny token'''
		return self.data[0]
