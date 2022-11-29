#!/usr/bin/env python
# -*- coding: utf-8 -*-

from google.appengine.ext import webapp
from google.appengine.ext.webapp.util import run_wsgi_app
from MessageBuilder import *
from PushConnection import *

class MainHandler(webapp.RequestHandler):
    def post(self):
        P = PushConnection(123456, 'wojtek@gg.pl', 'hasło')
        P.setStatus('Mój nowy opis', STATUS_AWAY)

application = webapp.WSGIApplication([('/', MainHandler)],debug=True)

def main():
    run_wsgi_app(application)

if __name__ == "__main__":
    main()