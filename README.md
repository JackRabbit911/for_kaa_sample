# for_kaa_sample
## Подготовка кода
1.  Перейти в папку www **`cd ~/www`**
2.  Склонировать имитатор командой **`git clone https://github.com/JackRabbit911/env_hostland for_kaa_sample`**
3.  Перейти в папку *for_kaa_sample* **`cd for_kaa_sample`**
4.  Создать папку *site.zone* командой **`mkdir site.zone`**
5.  Войти в неё комардой **`cd site.zone`**
6.  Создать папку *htdocs* командой **`mkdir htdocs`**
7.  Войти в неё комадой **`cd htdocs`**
8.  Теперь клонируем сюда этот реп **`git clone https://github.com/JackRabbit911/for_kaa_sample www`**
## Подготовка СУБД
1.  Перейти в папку *www* **`cd ~/www/for_kaa_sample`**
2.  Поднять имитатор **`docker-compose up`**
3.  Перейти по адресу http://localhost/todb Войти: Сервер: **`mysql`** username: **`root`** password: **`secret`**
4.  Создать базу **`test`**
5.  Создать пользователя **`kaadb`** со всеми правами на базу данных **`test`**
6.  Импортировать дамп (найти в корневой папке сайта) **`test.sql.gz`**
## Проверить работу системы
1.  Перейти по адресу http://localhost
2.  Попробовать залогиниться (kaa@yandex.ru/123456), разлогиниться