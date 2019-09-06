--
-- Скрипт сгенерирован Devart dbForge Studio for MySQL, Версия 7.4.201.0
-- Домашняя страница продукта: http://www.devart.com/ru/dbforge/mysql/studio
-- Дата скрипта: 22.08.2019 19:24:17
-- Версия сервера: 5.6.41
-- Версия клиента: 4.1
--

-- 
-- Отключение внешних ключей
-- 
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- 
-- Установить режим SQL (SQL mode)
-- 
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 
-- Установка кодировки, с использованием которой клиент будет посылать запросы на сервер
--
SET NAMES 'utf8';

--
-- Установка базы данных по умолчанию
--
USE tournament;

--
-- Удалить таблицу `players`
--
DROP TABLE IF EXISTS players;

--
-- Удалить таблицу `tournament`
--
DROP TABLE IF EXISTS tournament;

--
-- Удалить таблицу `user`
--
DROP TABLE IF EXISTS user;

--
-- Установка базы данных по умолчанию
--
USE tournament;

--
-- Создать таблицу `user`
--
CREATE TABLE user (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(20) NOT NULL,
  name varchar(50) NOT NULL,
  email varchar(320) NOT NULL,
  hash varchar(255) NOT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 15,
AVG_ROW_LENGTH = 16384,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_general_ci;

--
-- Создать индекс `user_login_uindex` для объекта типа таблица `user`
--
ALTER TABLE user
ADD UNIQUE INDEX user_login_uindex (username);

--
-- Создать таблицу `tournament`
--
CREATE TABLE tournament (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  datetime datetime DEFAULT NULL,
  owner_id int(11) NOT NULL,
  status enum ('awaiting', 'in progress', 'ended') NOT NULL DEFAULT 'awaiting',
  current_round int(11) DEFAULT NULL,
  round_count int(11) DEFAULT NULL,
  toss blob DEFAULT NULL COMMENT 'serilization of TOSS method result',
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 9,
AVG_ROW_LENGTH = 16384,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_general_ci;

--
-- Создать внешний ключ
--
ALTER TABLE tournament
ADD CONSTRAINT tournament_user_id_fk FOREIGN KEY (owner_id)
REFERENCES user (id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Создать таблицу `players`
--
CREATE TABLE players (
  id int(11) NOT NULL AUTO_INCREMENT,
  team varchar(50) DEFAULT NULL,
  nickname varchar(50) NOT NULL,
  tournament_id int(11) DEFAULT NULL,
  lifes int(1) DEFAULT 2,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 29,
AVG_ROW_LENGTH = 744,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_general_ci;

--
-- Создать внешний ключ
--
ALTER TABLE players
ADD CONSTRAINT players_tournament_id_fk FOREIGN KEY (tournament_id)
REFERENCES tournament (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Вывод данных для таблицы user
--
INSERT INTO user VALUES
(14, 'test', 'test', 'test@test.ru', '$2y$10$.S9UL//SxnMQWp.5K867fumxf0oiK1.SlAEe0EMvplOuIRrGdknva');

-- 
-- Вывод данных для таблицы tournament
--
INSERT INTO tournament VALUES
(8, 'Tournament', '2019-10-10 20:00:00', 14, 'awaiting', NULL, NULL, x'6E756C6C');

-- 
-- Вывод данных для таблицы players
--
INSERT INTO players VALUES
(7, 'Mirana', 'Player1', 8, 2),
(8, 'WAIT', 'Player2', 8, 2),
(9, 'OUT', 'Player3', 8, 2),
(10, 'OUT', 'Player4', 8, 2),
(11, 'OUT', 'Player5', 8, 2),
(12, 'OUT', 'Player6', 8, 2),
(13, 'Leshrac', 'Player7', 8, 2),
(14, 'OUT', 'Player8', 8, 2),
(15, 'Leshrac', 'Player9', 8, 2),
(16, 'OUT', 'Player10', 8, 2),
(17, 'OUT', 'Player11', 8, 2),
(18, 'Leshrac', 'Player12', 8, 2),
(19, 'OUT', 'Player13', 8, 2),
(20, 'OUT', 'Player14', 8, 2),
(21, 'OUT', 'Player15', 8, 2),
(22, 'OUT', 'Player16', 8, 2),
(23, 'OUT', 'Player17', 8, 2),
(24, 'Leshrac', 'Player18', 8, 2),
(25, 'OUT', 'Player19', 8, 2),
(26, 'Leshrac', 'Player20', 8, 2),
(27, 'OUT', 'Player21', 8, 2),
(28, 'OUT', 'Player22', 8, 2);

-- 
-- Восстановить предыдущий режим SQL (SQL mode)
-- 
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;

-- 
-- Включение внешних ключей
-- 
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;