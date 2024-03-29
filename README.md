[![github action status](https://github.com/petrdobr/test_case_image_parser/workflows/Symfony/badge.svg)](../../actions)
## Загрузчик изображений

### Для связи
email: dobrokhotovp@gmail.com

telegram: @petrdobr

### Описание
Небольшой одностраничный тестовый проект, написанный на фреймворке Symfony-6.4.9. С его помощью можно загружать изображения, просматривать их и получить краткий отчет о количестве и суммарном размере изображений.

На главной странице проекта расположена форма, в которую можно ввести адрес веб-страницы. Начинаться адрес должен с протокола `http://` или `https://`.

После нажатия кнопки "Го" приложение выводит превью всех доступных изображений с запрошенного адреса в ряд по 4 изображения. Каждое изображение можно открыть отдельно в его оригинальном разрешении нажатием на его превью в таблице. После таблицы выводится небольшой отчет, содержащий число скачанных изображений и их суммарный размер в мегабайтах.

Приложение сохраняет все скачанные изображения в папку `var\img\`.

Нажатие кнопки "Сброс" удаляет все изображения с главной страницы приложения и из папки `var\img\`.

В коде приложения добавлены поясняющие комментарии. Реализованы тесты для проверки работоспособности.

### Установка
Необходимо иметь установленный composer. Желательно иметь установленный symfony-cli.

Для установки приложения к себе на компьютер нужно скопировать данный репозиторий и ввести команду:
```
composer install
```
Для запуска сервера по адресу localhost:8000 можно воспользоваться командой
```
symfony server:start
```
Или запустить сервер любым другим способом.

### Спасибо за внимание!
