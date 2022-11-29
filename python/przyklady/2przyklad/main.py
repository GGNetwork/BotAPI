#!/usr/bin/env python
# -*- coding: utf-8 -*-

import cgi
from google.appengine.ext import webapp
from google.appengine.ext.webapp.util import run_wsgi_app
from MessageBuilder import *
from PushConnection import *

class MainHandler(webapp.RequestHandler):
    def post(self):
        M = MessageBuilder()
        msg = cgi.escape(self.request.body) # ta zmienna zawiera wiadomość wysłaną przez użytkownika do bota
        if msg == 'cześć': M.addText('Twój numer to %s' % (int(self.request.get('from'))))
        elif msg == 'kim jesteś?': M.addText('Jestem zielonym botem.')
        else: M.addText('Nie rozumiem... Napisz to innymi słowami.')
        self.response.out.write(M.reply(self))

application = webapp.WSGIApplication([('/', MainHandler)],debug=True)

def main():
    run_wsgi_app(application)

if __name__ == "__main__":
    main()
