#!/usr/bin/env python
# -*- coding: utf-8 -*-

'''
Biblioteka implementująca BotAPI GG http://boty.gg.pl/
Copyright (C) 2013 GG Network S.A. Marcin Bagiński <marcin.baginski@firma.gg.pl>

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
from MessageBuilder import *
from xml.etree import ElementTree


STATUS_AWAY='away'
STATUS_FFC='ffc'
STATUS_BACK='back'
STATUS_DND='dnd'
STATUS_INVISIBLE='invisible'
BOTAPI_VERSION='GGBotApi-2.4-PYTHON'

class PushConnection:
	''' Klasa reprezentująca połączenie PUSH z BotMasterem.
	Autoryzuje połączenie w trakcie tworzenia i wysyła wiadomości do BotMastera. '''
	URL_MESSAGE='https://%s/sendMessage/%d'
	URL_STATUS='https://%s/setStatus/%d'
	URL_IMG='https://botapi.gadu-gadu.pl/botmaster/%sImage/%d'
	URL_ISBOT='https://botapi.gadu-gadu.pl/botmaster/isBot/%d'

	lastAuthorization=None
	lastGg=None


	def __init__(self, ggid=None, userName=None, password=None):
		''' Konstruktor PushConnection - przeprowadza autoryzację

		ggid to numer GG bota.
		userName to login platformy BotAPI.
		password hasło do platformy BotAPI.'''


		if ((PushConnection.lastAuthorization is None) or (PushConnection.lastAuthorization.isAuthorized()==False) or (ggid!=PushConnection.lastGg and ggid is not None)):
			if ((userName is None) and (self.BOTAPI_LOGIN is not None)):
				userName = self.BOTAPI_LOGIN
			if ((password is None) and (self.BOTAPI_PASSWORD is not None)):
				password = self.BOTAPI_PASSWORD

			PushConnection.lastAuthorization=BotAPIAuthorization(ggid, userName, password)
			PushConnection.lastGg=ggid


		self.gg=PushConnection.lastGg
		self.authorization=PushConnection.lastAuthorization


	def __push(self, parmdictb, offline):
		data=self.authorization.getServerAndToken()
		request=urllib2.Request(PushConnection.URL_MESSAGE % (data[1], self.gg))
		request.add_header('BotApi-Version', BOTAPI_VERSION)
		request.add_header('Token', data[0])
		request.add_header('Send-to-offline', offline)

		postdata=urllib.unquote(parmdictb)
		request.add_data(postdata)

		file=urllib2.urlopen(request)
		xmlData=file.read()

		if re.search(r'<status>0<\/status>', xmlData) is not None:
			return 1
		return 0


	def push(self, messages):
		'''Wysyła listę wiadomości do BotMastera.'''

		if self.authorization.isAuthorized()==False:
			return 0


		count=0

		if isinstance(message, list)==False:
			messages=[messages]

		for message in messages:
			parmdictb=urllib.urlencode({'to': ','.join(map(str, message.recipientNumbers)), 'msg': urllib.quote(message.getProtocolMessage())})
			if message.sendToOffline:
				offline='1'
			else:
				offline='0'

			count+=self.__push(parmdictb, offline)

		return count


	def setStatus(self, statusDescription, status='', graphic=False):
		'''Ustawia opis botowi.

		statusDescription treść opisu
		status typ opisu
		graphic flaga czy jest to opis graficzny'''
		if self.authorization.isAuthorized()==False:
			return 0


		try:
			data=self.authorization.getServerAndToken()
			request=urllib2.Request(PushConnection.URL_STATUS % (data[1], self.gg))
			request.add_header('BotApi-Version', BOTAPI_VERSION)
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
		return ''


	def __image(self, type, dataName, post):
		try:
			data=self.authorization.getServerAndToken()

			request=urllib2.Request(PushConnection.URL_IMG % (type, self.gg))
			request.add_header('BotApi-Version', BOTAPI_VERSION)
			request.add_header('Token', data[0])

			if dataName == 'data':
				request.add_data(post)
			else:
				parmdictb=urllib.urlencode({dataName: urllib.quote(post)})
				postdata=urllib.unquote(parmdictb)
				request.add_data(postdata)

			file=urllib2.urlopen(request)
			xmlData=file.read()

			return xmlData;

		except urllib2.HTTPError, err:
			return False


	def putImage(self, data):
		if re.search(r'<status>0<\/status>', self.__image('put', 'data', data)) is not None:
			return 1
		return 0


	def getImage(self, hash):
		''' więcej logiki dla danych raw obrazka '''
		return self.__image('get', 'hash', hash)


	def existsImage(self, hash):
		if re.search(r'<status>0<\/status>', self.__image('exists', 'hash', hash)) is not None:
			return True
		return False


	def isBot(self, ggid):
		try:
			data=self.authorization.getServerAndToken()

			request=urllib2.Request(PushConnection.URL_ISBOT % (self.gg))
			request.add_header('BotApi-Version', BOTAPI_VERSION)
			request.add_header('Token', data[0])

			parmdictb=urllib.urlencode({'check_ggid': ggid})
			postdata=urllib.unquote(parmdictb)
			request.add_data(postdata)

			file=urllib2.urlopen(request)
			xmlData=file.read()

			if re.search(r'<status>0<\/status>', xmlData) is not None:
				return False
			return True

		except urllib2.HTTPError, err:
			return False



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
		request.add_header('BotApi-Version', BOTAPI_VERSION)
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
