#!/usr/bin/env python
# -*- coding: utf-8 -*-

import random
from google.appengine.ext import webapp
from google.appengine.ext.webapp.util import run_wsgi_app
from MessageBuilder import *
from PushConnection import *

class MainHandler(webapp.RequestHandler):
    def post(self):
        M = MessageBuilder()
        rand = random.randint(1, 7)
        if rand == 1: M.addText('Tekst pomarańczowy', FORMAT_NONE, 255, 165, 0)
        if rand == 2: M.addText('Tekst pogrubiony, pochylony i podkreślony', FORMAT_BOLD_TEXT | FORMAT_ITALIC_TEXT | FORMAT_UNDERLINE_TEXT)
        if rand == 3: M.addText('Tekst podkreślony w kolorze czerwonym', FORMAT_UNDERLINE_TEXT, 255, 0, 0)
        if rand == 4: M.addText('Pierwsza linia\nDruga linia')
        if rand == 5: M.addText('Tekst wysłany do innych').setRecipients([123,456])
        if rand == 6: M.addText('Tekst wysłany do Ciebie i innych').setRecipients([123,456,int(self.request.get('from'))])
        if rand == 7: M.addRawHtml('Tekst <b>pogrubiony</b> oraz <i>pochylony</i> oraz <u>podkreślony</u>')
        self.response.out.write(M.reply(self))

application = webapp.WSGIApplication([('/', MainHandler)],debug=True)

def main():
    run_wsgi_app(application)

if __name__ == "__main__":
    main()
