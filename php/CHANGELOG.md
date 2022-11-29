v.3.1:

* Optymalizacje
* Refactoringi
* Poprawki formatowania kodu
* Lepsze wsparcie dla autoloadingu. (wydzielono wyjątki itp do oddzielnych plików).
* Nowe pliki do bibliotek:
  - BotAPIAuthorization.php
  - MessageBuilderException.php
  - PushConnectionException.php
  - TokenExpiredException.php
  - UnableToSendImageException.php
  - UnableToSendMessageException.php
  - UnableToSetStatusExteption.php

Zmiany związane z klasą MessageBuilder:

* Ogólne:
  - Stałe BOTAPI_VERSION, IMG_RAW, IMG_FILE przeniesione do klasy. (można się do ich odwołać używając przedrostka "MessageBuilder::")
  - Nowe wyjątki które można złapać przez klasę bazową "MessageBuilderException"
* Własności: $html, $text, $format - zmieniono modyfikatory dostępu z public na private. 

* Metody:
  - addImage(): Rzucanie wyjątku UnableToSendImageException wrazie niepowodzenia.
                Naprawiono wywołanie addImage('zawartość obrazka', MessageBuilder::IMG_RAW);

Zmiany związane z klasą PushConnection:
  - Stała CURL_VERBOSE przeniesiona do klasy.

=====

v.3.0:

* Wymagane PHP 5.6+

Zmiany związane z klasą MessageBuilder:

* Ogólne:
  - Skasowano lokalne stałe które nie będą więcej potrzebne: FORMAT_NONE, FORMAT_BOLD_TEXT, FORMAT_ITALIC_TEXT, FORMAT_UNDERLINE_TEXT, FORMAT_NEW_LINE.
  - Kod powiązany z BBcode skasowany.
* Metody:
  - addBBcode(), setSendToOffline(): skasowano - nie wspierane już.
  - addText(): parametry powiązane z BBcode skasowane.

Zmiany związane z klasą PushConnection:

* Ogólne:
  - Stałe lokalne STATUS_* przeniesione do klasy, można się do ich zewnętrznie odwołać używając przedrostka "PushConnection::"

* Metody:
  - push(): Rzucanie wyjątku klasy UnableToSendMessageException w przypadku niepowodzenia.
  - sendToOffline(): skasowano - nie wspierane już.
  - setStatus(): Rzucanie wyjątku klasy UnableToSetStatusExteption w przypadku niepowodzenia.

=====

v.2.5:

* Wymagane PHP 5.4+
* Optymalizacje
* Skasowanie deporcjonowanych zmiennych, metod. ("Opisy graficzne")
* $m->r, $m->g, $m->b od teraz jest w tablicy $m->rgb[RR, GG, BB]
