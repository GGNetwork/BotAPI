#!/usr/bin/env python
# -*- coding: utf-8 -*-

import cgi
from google.appengine.ext import webapp
from google.appengine.ext.webapp.util import run_wsgi_app
from MessageBuilder import *
from PushConnection import *

class MainHandler(webapp.RequestHandler):
    def post(self):
        PushConnection.BOTAPI_LOGIN = 'wojtek@gg.pl'
        PushConnection.BOTAPI_PASSWORD = 'hasło'
        P = PushConnection(123456)
        M = MessageBuilder()
        msg = cgi.escape(self.request.body) # ta zmienna zawiera wiadomość wysłaną przez użytkownika do bota
        if msg == 'kot':
            M.addText('Oto kot:')
            M.addImage('kot.jpg')
        else:
            M.addText('A to jest GG:')
            M.addImage('gg.png')
        self.response.out.write(M.reply(self))

application = webapp.WSGIApplication([('/', MainHandler)],debug=True)

def main():
    run_wsgi_app(application)

if __name__ == "__main__":
    main()