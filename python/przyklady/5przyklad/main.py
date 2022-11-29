#!/usr/bin/env python
# -*- coding: utf-8 -*-

from google.appengine.ext import webapp
from google.appengine.ext.webapp.util import run_wsgi_app
from MessageBuilder import *
from PushConnection import *

class MainHandler(webapp.RequestHandler):
    def post(self):
        M = MessageBuilder()
        M.addText('Zapraszam na http://boty.gg.pl/');
        M.setRecipients([123,456])
        P = PushConnection(123456, 'wojtek@gg.pl', 'hasło')
        P.push(M)

application = webapp.WSGIApplication([('/', MainHandler)],debug=True)

def main():
    run_wsgi_app(application)

if __name__ == "__main__":
    main()